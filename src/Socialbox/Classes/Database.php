<?php

    namespace Socialbox\Classes;

    use PDO;
    use PDOException;
    use Socialbox\Classes\Configuration\DatabaseConfiguration;
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
                $dsn = Configuration::getDatabaseConfiguration()->getDsn();

                try
                {
                    self::$instance = new PDO($dsn, Configuration::getDatabaseConfiguration()->getUsername(), Configuration::getDatabaseConfiguration()->getPassword());

                    // Set some common PDO attributes for better error handling
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                }
                catch (PDOException $e)
                {
                    throw new DatabaseOperationException('Failed to connect to the database using ' . $dsn, $e);
                }
            }

            return self::$instance;
        }

    }