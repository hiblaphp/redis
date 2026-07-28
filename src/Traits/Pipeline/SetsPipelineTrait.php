<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Sets\SaddCommand;
use Hibla\Redis\Command\Sets\ScardCommand;
use Hibla\Redis\Command\Sets\SdiffCommand;
use Hibla\Redis\Command\Sets\SinterCommand;
use Hibla\Redis\Command\Sets\SismemberCommand;
use Hibla\Redis\Command\Sets\SmembersCommand;
use Hibla\Redis\Command\Sets\SpopCommand;
use Hibla\Redis\Command\Sets\SremCommand;
use Hibla\Redis\Command\Sets\SscanCommand;
use Hibla\Redis\Command\Sets\SunionCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait SetsPipelineTrait
{
    /**
     * @template TResponse
     *
     * @param CommandInterface<TResponse> $command
     *
     * @return self
     */
    abstract public function executeCommand(CommandInterface $command): self;

    /**
     * Adds a SADD command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed ...$members Members to add.
     *
     * @return self For method chaining.
     */
    public function sadd(string $key, mixed ...$members): self
    {
        return $this->executeCommand(new SaddCommand([$key, ...$members]));
    }

    /**
     * Adds a SREM command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed ...$members Members to remove.
     *
     * @return self For method chaining.
     */
    public function srem(string $key, mixed ...$members): self
    {
        return $this->executeCommand(new SremCommand([$key, ...$members]));
    }

    /**
     * Adds a SMEMBERS command to the pipeline.
     *
     * @param string $key The set key.
     *
     * @return self For method chaining.
     */
    public function smembers(string $key): self
    {
        return $this->executeCommand(new SmembersCommand([$key]));
    }

    /**
     * Adds a SISMEMBER command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed $member Member to test.
     *
     * @return self For method chaining.
     */
    public function sismember(string $key, mixed $member): self
    {
        return $this->executeCommand(new SismemberCommand([$key, $member]));
    }

    /**
     * Adds an SCARD command to the pipeline.
     *
     * @param string $key Set key.
     *
     * @return self For method chaining.
     */
    public function scard(string $key): self
    {
        return $this->executeCommand(new ScardCommand([$key]));
    }

    /**
     * Adds an SPOP command to the pipeline.
     *
     * @param string $key Set key.
     * @param int|null $count Number of members to pop.
     *
     * @return self For method chaining.
     */
    public function spop(string $key, ?int $count = null): self
    {
        $args = $count === null ? [$key] : [$key, $count];

        return $this->executeCommand(new SpopCommand($args));
    }

    /**
     * Adds an SINTER command to the pipeline.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to intersect.
     *
     * @return self For method chaining.
     */
    public function sinter(string|array $keys, string ...$moreKeys): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        foreach ($moreKeys as $key) {
            $args[] = $key;
        }

        return $this->executeCommand(new SinterCommand($args));
    }

    /**
     * Adds an SUNION command to the pipeline.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to union.
     *
     * @return self For method chaining.
     */
    public function sunion(string|array $keys, string ...$moreKeys): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        foreach ($moreKeys as $key) {
            $args[] = $key;
        }

        return $this->executeCommand(new SunionCommand($args));
    }

    /**
     * Adds an SDIFF command to the pipeline.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to diff against.
     *
     * @return self For method chaining.
     */
    public function sdiff(string|array $keys, string ...$moreKeys): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        foreach ($moreKeys as $key) {
            $args[] = $key;
        }

        return $this->executeCommand(new SdiffCommand($args));
    }

    /**
     * Adds an SSCAN command to the pipeline.
     *
     * @param string $key The set key.
     * @param string|int $cursor The cursor to start the scan from (use '0' for a new scan).
     * @param string|null $match Glob-style pattern to match member names against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     *
     * @return self For method chaining.
     */
    public function sscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): self
    {
        $args = [$key, (string) $cursor];

        if ($match !== null) {
            $args[] = 'MATCH';
            $args[] = $match;
        }

        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        return $this->executeCommand(new SscanCommand($args));
    }
}
