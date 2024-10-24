<?php

namespace Socialbox\Classes\CacheLayer;

use Redis;
use RedisException;
use RuntimeException;
use Socialbox\Abstracts\CacheLayer;
use Socialbox\Classes\Configuration;

class RedisCacheLayer extends CacheLayer
{
    private Redis $redis;

    /**
     * Redis cache layer constructor.
     */
    public function __construct()
    {
        if (!extension_loaded('redis'))
        {
            throw new RuntimeException('The Redis extension is not loaded in your PHP environment.');
        }

        $this->redis = new Redis();

        try
        {
            $this->redis->connect(Configuration::getCacheConfiguration()->getHost(), Configuration::getCacheConfiguration()->getPort());
            if (Configuration::getCacheConfiguration()->getPassword() !== null)
            {
                $this->redis->auth(Configuration::getCacheConfiguration()->getPassword());
            }

            if (Configuration::getCacheConfiguration()->getDatabase() !== null)
            {
                $this->redis->select(Configuration::getCacheConfiguration()->getDatabase());
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
    public function getPrefixCount(string $prefix): int
    {
        try
        {
            return count($this->redis->keys($prefix . '*'));
        }
        catch (RedisException $e)
        {
            throw new RuntimeException('Failed to get the count of keys with the specified prefix in the Redis cache.', 0, $e);
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