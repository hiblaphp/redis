<?php

declare(strict_types=1);

use Hibla\EventLoop\Loop;
use Hibla\Promise\Exceptions\CancelledException;
use Hibla\Promise\Promise;
use Hibla\Redis\Exceptions\ConnectionException;
use Hibla\Redis\Exceptions\RedisException;
use Hibla\Redis\Exceptions\TimeoutException;
use Hibla\Redis\RedisClient;
use Hibla\Redis\ValueObjects\RedisConfig;
use Hibla\Redis\ValueObjects\RetryConfig;
use Hibla\Socket\Interfaces\ConnectionInterface as SocketConnection;
use Hibla\Socket\Interfaces\ConnectorInterface;

afterEach(function () {
    Mockery::close();
    Loop::reset();
});

function createMockSocket(
    ?callable &$dataHandler = null,
    ?callable &$errorHandler = null,
    ?callable &$closeHandler = null
): Mockery\MockInterface {
    $socket = Mockery::mock(SocketConnection::class);

    $socket->shouldReceive('on')->with('data', Mockery::on(function ($cb) use (&$dataHandler) {
        $dataHandler = $cb;

        return true;
    }))->byDefault();

    $socket->shouldReceive('on')->with('error', Mockery::on(function ($cb) use (&$errorHandler) {
        $errorHandler = $cb;

        return true;
    }))->byDefault();

    $socket->shouldReceive('on')->with('close', Mockery::on(function ($cb) use (&$closeHandler) {
        $closeHandler = $cb;

        return true;
    }))->byDefault();

    $socket->shouldReceive('close')->byDefault();

    return $socket;
}

test('it does not retry on protocol or logic errors', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 3, baseDelay: 0.01);

    $connector = Mockery::mock(ConnectorInterface::class);
    $socket = createMockSocket($dataHandler);

    $connector->shouldReceive('connect')->once()->andReturn(Promise::resolved($socket));
    $socket->shouldReceive('write')->once()->andReturn(true);

    $client = new RedisClient($config, connector: $connector, retryConfig: $retryConfig);
    $promise = $client->get('bad_key');

    Loop::addTimer(0.01, function () use (&$dataHandler) {
        expect($dataHandler)->not->toBeNull();
        $dataHandler("-WRONGTYPE Operation against a key holding the wrong kind of value\r\n");
    });

    expect(fn () => $promise->wait())
        ->toThrow(RedisException::class, 'WRONGTYPE Operation against a key holding the wrong kind of value')
    ;
});

test('it retries on connection failure and respects backoff delay', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 3, baseDelay: 0.05, jitter: false);

    $connector = Mockery::mock(ConnectorInterface::class);
    $failingSocket = createMockSocket(errorHandler: $failingErrorHandler);
    $successSocket = createMockSocket(dataHandler: $successDataHandler);

    $connector->shouldReceive('connect')->times(2)->andReturn(
        Promise::resolved($failingSocket),
        Promise::resolved($successSocket)
    );

    $failingSocket->shouldReceive('write')->once()->andReturnUsing(function () use (&$failingErrorHandler) {
        Loop::microTask(fn () => $failingErrorHandler(new RuntimeException('Connection reset by peer')));

        return true;
    });

    $successSocket->shouldReceive('write')->once()->andReturn(true);

    $client = new RedisClient($config, connector: $connector, retryConfig: $retryConfig);
    $promise = $client->get('test_key');

    Loop::addTimer(0.06, function () use (&$successDataHandler) {
        expect($successDataHandler)->not->toBeNull();
        $successDataHandler("$5\r\nvalue\r\n");
    });

    $startTime = microtime(true);
    $result = $promise->wait();
    $duration = microtime(true) - $startTime;

    expect($result)->toBe('value')
        ->and($duration)->toBeGreaterThanOrEqual(0.05)
    ;
});

test('cancellation during backoff prevents further retries and destroys timer', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 3, baseDelay: 0.5, jitter: false);

    $connector = Mockery::mock(ConnectorInterface::class);
    $socket = createMockSocket(errorHandler: $errorHandler);

    $connector->shouldReceive('connect')->once()->andReturn(Promise::resolved($socket));
    $socket->shouldReceive('write')->once()->andReturnUsing(function () use (&$errorHandler) {
        Loop::microTask(fn () => $errorHandler(new RuntimeException('Socket dropped')));

        return true;
    });

    $client = new RedisClient($config, connector: $connector, retryConfig: $retryConfig);
    $promise = $client->get('test_key');

    Loop::addTimer(0.1, function () use ($promise) {
        $promise->cancel();
    });

    $startTime = microtime(true);

    expect(fn () => $promise->wait())->toThrow(CancelledException::class);

    $duration = microtime(true) - $startTime;
    expect($duration)->toBeLessThan(0.4);
});

test('it throws ConnectionException when max retries are exceeded', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 2, baseDelay: 0.01, jitter: false);

    $connector = Mockery::mock(ConnectorInterface::class);
    $socket = createMockSocket(errorHandler: $errorHandler);
    $connector->shouldReceive('connect')->times(3)->andReturn(Promise::resolved($socket));

    $socket->shouldReceive('write')->times(3)->andReturnUsing(function () use (&$errorHandler) {
        Loop::microTask(fn () => $errorHandler(new RuntimeException('Timeout')));

        return true;
    });

    $client = new RedisClient($config, connector: $connector, retryConfig: $retryConfig);
    $promise = $client->get('test_key');

    expect(fn () => $promise->wait())
        ->toThrow(ConnectionException::class, 'Timeout')
    ;
});

test('pool manager self-heals in the background', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 3, baseDelay: 0.02, jitter: false);
    $connector = Mockery::mock(ConnectorInterface::class);

    $connector->shouldReceive('connect')->once()->andReturn(Promise::rejected(new RuntimeException('Redis Down')));

    $successSocket = createMockSocket();
    $connector->shouldReceive('connect')->once()->andReturn(Promise::resolved($successSocket));

    $client = new RedisClient(
        $config,
        minConnections: 1,
        connector: $connector,
        retryConfig: $retryConfig
    );

    Loop::addTimer(0.01, function () use ($client) {
        expect($client->stats['total_connections'])->toBe(0);
    });

    Loop::addTimer(0.05, function () use ($client) {
        expect($client->stats['total_connections'])->toBe(1)
            ->and($client->stats['pooled_connections'])->toBe(1)
        ;
    });

    Loop::run();
});

test('pipeline retries entire batch on connection drop', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 1, baseDelay: 0.01, jitter: false);

    $connector = Mockery::mock(ConnectorInterface::class);
    $failingSocket = createMockSocket(errorHandler: $failingErrorHandler);
    $successSocket = createMockSocket(dataHandler: $successDataHandler);

    $connector->shouldReceive('connect')->times(2)->andReturn(
        Promise::resolved($failingSocket),
        Promise::resolved($successSocket)
    );

    $failingSocket->shouldReceive('write')->once()->andReturnUsing(function () use (&$failingErrorHandler) {
        Loop::microTask(fn () => $failingErrorHandler(new RuntimeException('Network drop')));

        return true;
    });

    $successSocket->shouldReceive('write')->once()->andReturn(true);

    $client = new RedisClient($config, connector: $connector, retryConfig: $retryConfig);

    $promise = $client->pipeline(function ($pipe) {
        $pipe->set('key', 'value')->get('key');
    });

    Loop::addTimer(0.02, function () use (&$successDataHandler) {
        $successDataHandler("+OK\r\n$5\r\nvalue\r\n");
    });

    $result = $promise->wait();

    expect($result)->toBe(['OK', 'value']);
});

test('atomic block retries entire transaction on connection drop', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 1, baseDelay: 0.01, jitter: false);

    $connector = Mockery::mock(ConnectorInterface::class);
    $failingSocket = createMockSocket(errorHandler: $failingErrorHandler);
    $successSocket = createMockSocket(dataHandler: $successDataHandler);

    $connector->shouldReceive('connect')->times(2)->andReturn(
        Promise::resolved($failingSocket),
        Promise::resolved($successSocket)
    );

    $failingSocket->shouldReceive('write')->once()->andReturnUsing(function () use (&$failingErrorHandler) {
        Loop::microTask(fn () => $failingErrorHandler(new RuntimeException('Connection severed')));

        return true;
    });

    $successSocket->shouldReceive('write')->once()->andReturn(true);

    $client = new RedisClient($config, connector: $connector, retryConfig: $retryConfig);

    $promise = $client->atomic(function ($pipe) {
        $pipe->set('key', 'value')->get('key');
    });

    Loop::addTimer(0.02, function () use (&$successDataHandler) {
        $successDataHandler("+OK\r\n+QUEUED\r\n+QUEUED\r\n*2\r\n+OK\r\n$5\r\nvalue\r\n");
    });

    $result = $promise->wait();

    expect($result)->toBe(['OK', 'value']);
});

test('it fails immediately without retrying when maxRetries is 0', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 0, baseDelay: 0.1);

    $connector = Mockery::mock(ConnectorInterface::class);
    $socket = createMockSocket(errorHandler: $errorHandler);

    $connector->shouldReceive('connect')->once()->andReturn(Promise::resolved($socket));

    $socket->shouldReceive('write')->once()->andReturnUsing(function () use (&$errorHandler) {
        Loop::microTask(fn () => $errorHandler(new RuntimeException('Instant fail')));

        return true;
    });

    $client = new RedisClient($config, connector: $connector, retryConfig: $retryConfig);
    $promise = $client->get('test_key');

    $startTime = microtime(true);

    expect(fn () => $promise->wait())
        ->toThrow(ConnectionException::class, 'Instant fail')
    ;

    $duration = microtime(true) - $startTime;
    expect($duration)->toBeLessThan(0.05);
});

test('cancelling a command while waiting in the pool queue cleans up the waiter', function () {
    $config = new RedisConfig(host: '127.0.0.1');
    $retryConfig = new RetryConfig(maxRetries: 1);

    $connector = Mockery::mock(ConnectorInterface::class);
    $socket = createMockSocket();

    $client = new RedisClient(
        $config,
        maxConnections: 1,
        connector: $connector,
        retryConfig: $retryConfig
    );

    $connector->shouldReceive('connect')->once()->andReturn(Promise::resolved($socket));
    $socket->shouldReceive('write')->once()->andReturn(true);

    $promise1 = $client->get('key_1');

    Loop::runOnce();

    $promise2 = $client->get('key_2');

    expect($client->stats['waiting_requests'])->toBe(1);

    $promise2->cancel();

    expect(fn () => $promise2->wait())->toThrow(CancelledException::class);
});

test('it throws TimeoutException if it cannot acquire a connection from a full pool in time', function () {
    $config = new RedisConfig(host: '127.0.0.1');

    $connector = Mockery::mock(ConnectorInterface::class);
    $socket = createMockSocket();

    $client = new RedisClient(
        $config,
        maxConnections: 1,
        acquireTimeout: 0.05,
        connector: $connector
    );

    $connector->shouldReceive('connect')->once()->andReturn(Promise::resolved($socket));
    $socket->shouldReceive('write')->once()->andReturn(true);

    $promise1 = $client->get('key_1');

    $promise2 = $client->get('key_2');

    $startTime = microtime(true);

    expect(fn () => $promise2->wait())
        ->toThrow(TimeoutException::class, 'Acquire timeout of 0.05s exceeded')
    ;

    $duration = microtime(true) - $startTime;
    expect($duration)->toBeGreaterThanOrEqual(0.05);
});
