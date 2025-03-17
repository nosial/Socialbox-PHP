<?php

    namespace Socialbox\Managers;

    use PDO;
    use PDOException;
    use Socialbox\Classes\CacheLayer;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Database;
    use Socialbox\Exceptions\DatabaseOperationException;

    class VariableManager
    {
        /**
         * Sets a variable in the database. If the variable already exists, its value is updated.
         *
         * @param string $name The name of the variable.
         * @param string $value The value of the variable.
         * @return void
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function setVariable(string $name, string $value): void
        {
            try
            {
                $statement = Database::getConnection()->prepare("INSERT INTO variables (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value=?");
                $statement->bindParam(1, $name);
                $statement->bindParam(2, $value);
                $statement->bindParam(3, $value);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to set variable %s in the database', $name), $e);
            }
            // TODO: Re-implement caching
            //finally
            //{
                //if(Configuration::getConfiguration()['cache']['enabled'] && Configuration::getConfiguration()['cache']['variables']['enabled'])
                //{
                //    if(Configuration::getConfiguration()['cache']['variables']['max'] > 0)
                //    {
                //        if(CacheLayer::getPrefixCount('VARIABLES_') >= Configuration::getConfiguration()['cache']['variables']['max'])
                //        {
                //            // Return early if the cache is full
                //            return;
                //        }
                //    }

                //    CacheLayer::getInstance()->set(sprintf("VARIABLES_%s", $name), $value, (int)Configuration::getConfiguration()['cache']['variables']['ttl']);
                //}
            //}
        }

        /**
         * Retrieves the value of a variable from the database based on its name.
         *
         * @param string $name The name of the variable to retrieve.
         * @return string The value of the variable.
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function getVariable(string $name): string
        {
            if(Configuration::getConfiguration()['cache']['enabled'] && Configuration::getConfiguration()['cache']['variables']['enabled'])
            {
                $cachedValue = CacheLayer::getInstance()->get(sprintf("VARIABLES_%s", $name));
                if($cachedValue !== false)
                {
                    return $cachedValue;
                }
            }

            try
            {
                $statement = Database::getConnection()->prepare("SELECT value FROM variables WHERE name=?");
                $statement->bindParam(1, $name);
                $statement->execute();

                if($statement->rowCount() === 0)
                {
                    throw new DatabaseOperationException(sprintf('Variable with name %s does not exist', $name));
                }

                $result = $statement->fetch(PDO::FETCH_ASSOC);
                return $result['value'];
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to get variable %s from the database', $name), $e);
            }
        }

        /**
         * Checks if a variable with the specified name exists in the database.
         *
         * @param string $name The name of the variable to check for existence.
         * @return bool Returns true if the variable exists, false otherwise.
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function variableExists(string $name): bool
        {
            if(Configuration::getConfiguration()['cache']['enabled'] && Configuration::getConfiguration()['cache']['variables']['enabled'])
            {
                $cachedValue = CacheLayer::getInstance()->get(sprintf("VARIABLES_%s", $name));
                if($cachedValue !== false)
                {
                    return true;
                }
            }

            try
            {
                $statement = Database::getConnection()->prepare("SELECT COUNT(*) FROM variables WHERE name=?");
                $statement->bindParam(1, $name);
                $statement->execute();
                $result = $statement->fetchColumn();
                return $result > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to check if the variable %s exists', $name), $e);
            }
        }

        /**
         * Deletes a variable from the database using the provided name.
         *
         * @param string $name The name of the variable to be deleted.
         * @return void
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function deleteVariable(string $name): void
        {
            try
            {
                $statement = Database::getConnection()->prepare("DELETE FROM variables WHERE name=?");
                $statement->bindParam(1, $name);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to delete variable %s from the database', $name), $e);
            }
            finally
            {
                if(Configuration::getConfiguration()['cache']['enabled'] && Configuration::getConfiguration()['cache']['variables']['enabled'])
                {
                    CacheLayer::getInstance()->delete(sprintf("VARIABLES_%s", $name));
                }
            }
        }
    }