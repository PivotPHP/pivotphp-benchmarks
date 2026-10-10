<?php

declare(strict_types=1);

namespace Bench\Support;

use Predis\Client;

/**
 * Cache Redis com degradação graciosa: se o Redis estiver indisponível,
 * as operações viram no-op e a API continua funcionando via banco.
 */
final class Cache
{
    public function __construct(private ?Client $redis)
    {
    }

    public function get(string $key): ?string
    {
        try {
            $value = $this->redis?->get($key);

            return $value === null ? null : (string) $value;
        } catch (\Throwable) {
            return null;
        }
    }

    public function set(string $key, string $value, int $ttl = 30): void
    {
        try {
            $this->redis?->setex($key, $ttl, $value);
        } catch (\Throwable) {
            // no-op
        }
    }

    public function del(string ...$keys): void
    {
        try {
            $this->redis?->del($keys);
        } catch (\Throwable) {
            // no-op
        }
    }
}
