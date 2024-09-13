<?php

namespace Socialbox\Classes\CacheLayer;

use Redis;
use RedisException;
use RuntimeException;
use Socialbox\Abstracts\CacheLayer;

class RedisCacheLayer extends CacheLayer
{
    private Redis $redis;

    /**
     * Redis cache layer constructor.
     *
     * @param string $host The Redis server host.
     * @param int $port The Redis server port.
     * @param string|null $password Optional. The Redis server password.
     */
    public function __construct(string $host, int $port, ?string $password=null)
    {
        if (!extension_loaded('redis'))
        {
            throw new RuntimeException('The Redis extension is not loaded in your PHP environment.');
        }

        $this->redis = new Redis();

        try
        {
            $this->redis->connect($host, $port);
            if ($password !== null)
            {
                $this->redis->auth($password);
            }
        }
        catch (RedisException $e)
        {
            throw new RuntimeException('Failed to connect to the Redis server.', 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        try
        {
            return $this->redis->set($key, $value, $ttl);
        }
        catch (RedisException $e)
        {
            throw new RuntimeException('Failed to set the value in the Redis cache.', 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        try
        {
            return $this->redis->get($key);
        }
        catch (RedisException $e)
        {
            throw new RuntimeException('Failed to get the value from the Redis cache.', 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        try
        {
            return $this->redis->del($key) > 0;
        }
        catch (RedisException $e)
        {
            throw new RuntimeException('Failed to delete the value from the Redis cache.', 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        try
        {
            return $this->redis->exists($key);
        }
        catch (RedisException $e)
        {
            throw new RuntimeException('Failed to check if the key exists in the Redis cache.', 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        try
        {
            return $this->redis->flushAll();
        }
        catch (RedisException $e)
        {
            throw new RuntimeException('Failed to clear the Redis cache.', 0, $e);
        }
    }
}