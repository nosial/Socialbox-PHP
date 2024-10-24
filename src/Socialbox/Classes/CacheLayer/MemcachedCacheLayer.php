<?php

namespace Socialbox\Classes\CacheLayer;

use Memcached;
use RuntimeException;
use Socialbox\Abstracts\CacheLayer;
use Socialbox\Classes\Configuration;

class MemcachedCacheLayer extends CacheLayer
{
    private Memcached $memcached;

    /**
     * Memcached cache layer constructor.
     */
    public function __construct()
    {
        if (!extension_loaded('memcached'))
        {
            throw new RuntimeException('The Memcached extension is not loaded in your PHP environment.');
        }

        $this->memcached = new Memcached();
        $this->memcached->addServer(Configuration::getCacheConfiguration()->getHost(), Configuration::getCacheConfiguration()->getPort());
        if(Configuration::getCacheConfiguration()->getUsername() !== null || Configuration::getCacheConfiguration()->getPassword() !== null)
        {
            $this->memcached->setSaslAuthData(Configuration::getCacheConfiguration()->getUsername(), Configuration::getCacheConfiguration()->getPassword());
        }
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        if (!$this->memcached->set($key, $value, $ttl))
        {
            throw new RuntimeException('Failed to set the value in the Memcached cache.');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        $result = $this->memcached->get($key);

        if ($this->memcached->getResultCode() !== Memcached::RES_SUCCESS)
        {
            throw new RuntimeException('Failed to get the value from the Memcached cache.');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        if (!$this->memcached->delete($key) && $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND)
        {
            throw new RuntimeException('Failed to delete the value from the Memcached cache.');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        $this->memcached->get($key);
        return $this->memcached->getResultCode() === Memcached::RES_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    public function getPrefixCount(string $prefix): int
    {
        $stats = $this->memcached->getStats();
        $count = 0;

        foreach ($stats as $server => $data)
        {
            if (str_starts_with($server, $prefix))
            {
                $count += $data['curr_items'];
            }
        }

        return $count;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        if (!$this->memcached->flush())
        {
            throw new RuntimeException('Failed to clear the Memcached cache.');
        }

        return true;
    }
}
