<?php

namespace Socialbox\Abstracts;

abstract class CacheLayer
{
    /**
     * Stores a value in the cache with an associated key and an optional time-to-live (TTL).
     *
     * @param string $key The key under which the value is stored.
     * @param mixed $value The value to be stored.
     * @param int $ttl Optional. The time-to-live for the cache entry in seconds. A value of 0 indicates no expiration.
     * @return bool Returns true if the value was successfully set, false otherwise.
     */
    public abstract function set(string $key, mixed $value, int $ttl=0): bool;

    /**
     * Retrieves a value from the cache with the specified key.
     *
     * @param string $key The key of the value to retrieve.
     * @return mixed The value associated with the key, or null if the key does not exist.
     */
    public abstract function get(string $key): mixed;

    /**
     * Deletes a value from the cache with the specified key.
     *
     * @param string $key The key of the value to delete.
     * @return bool Returns true if the value was successfully deleted, false otherwise.
     */
    public abstract function delete(string $key): bool;

    /**
     * Checks if a value exists in the cache with the specified key.
     *
     * @param string $key The key to check.
     * @return bool Returns true if the key exists, false otherwise.
     */
    public abstract function exists(string $key): bool;

    /**
     * Clears all values from the cache.
     *
     * @return bool Returns true if the cache was successfully cleared, false otherwise.
     */
    public abstract function clear(): bool;
}