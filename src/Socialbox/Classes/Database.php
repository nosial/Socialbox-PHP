<?php

namespace Socialbox\Classes;

use PDO;
use PDOException;
use Socialbox\Exceptions\DatabaseOperationException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * @return PDO
     * @throws DatabaseOperationException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null)
        {
            try
            {
                $dsn = 'mysql:host=127.0.0.1;dbname=socialbox;port=3306;charset=utf8mb4';
                self::$instance = new PDO($dsn, 'root', 'root');

                // Set some common PDO attributes for better error handling
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to connect to the database', $e);
            }
        }

        return self::$instance;
    }

}