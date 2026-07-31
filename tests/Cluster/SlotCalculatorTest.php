<?php

declare(strict_types=1);

use Hibla\Redis\Cluster\SlotCalculator;

describe('SlotCalculator', function (): void {
    it('calculates correct slots for known Redis key vectors', function (string $key, int $expectedSlot): void {
        expect(SlotCalculator::calculate($key))->toBe($expectedSlot);
    })->with([
        ['123456789', 12739],
        ['foo', 12182],
        ['bar', 5061],
        ['hello', 866],
    ]);

    it('extracts hash tags correctly across various key positions', function (string $keyWithTag, string $expectedEquivalentKey): void {
        expect(SlotCalculator::calculate($keyWithTag))
            ->toBe(SlotCalculator::calculate($expectedEquivalentKey))
        ;
    })->with([
        ['{user1000}.following', 'user1000'],
        ['{user1000}.followers', 'user1000'],
        ['user1000{tag}', 'tag'],
        ['prefix{tag}suffix', 'tag'],
        ['{a}', 'a'],
        ['{123}', '123'],
        ['{user:100:profile}.details', 'user:100:profile'],
    ]);

    it('handles complex and malformed hash tag edge cases matching Redis & Predis spec', function (string $key, string $expectedEquivalentKey): void {
        expect(SlotCalculator::calculate($key))
            ->toBe(SlotCalculator::calculate($expectedEquivalentKey))
        ;
    })->with([
        ['foo{}bar', 'foo{}bar'],
        ['{foo}{bar}', 'foo'],
        ['prefix{first}mid{second}suffix', 'first'],
        ['{foo{bar}}', 'foo{bar'],
        ['{{}}', '{'],
        ['{{{a}}}', '{{a'],
        ['foo{bar', 'foo{bar'],
        ['{{{', '{{{'],
        ['foo}bar', 'foo}bar'],
        ['}}}', '}}}'],
        ['foo}bar{baz', 'foo}bar{baz'],
        ['foo}bar{baz}', 'baz'],
    ]);

    it('handles UTF-8, binary null bytes, and non-ASCII keys/tags correctly', function (string $keyWithTag, string $expectedEquivalentKey): void {
        expect(SlotCalculator::calculate($keyWithTag))
            ->toBe(SlotCalculator::calculate($expectedEquivalentKey))
        ;
    })->with([
        ['{ユーザー1}.data', 'ユーザー1'],
        ['profile:{ユーザー1}', 'ユーザー1'],
        ['user:{🔥}:session', '🔥'],
        ['{🚀}', '🚀'],
        ["prefix{\0tag\0}suffix", "\0tag\0"],
        ["key{\n\ttag\t\n}end", "\n\ttag\t\n"],
        ['0', '0'],
        ['-1', '-1'],
        ['9999999999999999999999', '9999999999999999999999'],
    ]);

    it('handles very large key payloads (1KB+) efficiently without degradation', function (): void {
        $largeTag = str_repeat('A', 1024);
        $largeKey = 'prefix{' . $largeTag . '}suffix';

        expect(SlotCalculator::calculate($largeKey))
            ->toBe(SlotCalculator::calculate($largeTag))
        ;
    });

    it('always returns a slot within the valid 0 to 16383 range', function (): void {
        for ($i = 0; $i < 100; $i++) {
            $slot = SlotCalculator::calculate('random_key_' . $i . '_' . bin2hex(random_bytes(8)));
            expect($slot)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(16383);
        }
    });
});
