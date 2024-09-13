<?php

namespace Socialbox\Classes;

class CacheLayer
{
    private static CacheLayer $instance;

    public static function getInstance(): CacheLayer
    {
        if (!isset(self::$instance))
        {
            self::$instance = new CacheLayer();
        }

        return self::$instance;
    }
}