<?php

namespace Socialbox\Managers;

use DateTime;
use Exception;
use PDOException;
use Socialbox\Classes\Database;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Objects\Database\ResolvedServerRecord;
use Socialbox\Objects\ResolvedServer;

class ResolvedServersManager
{
    /**
     * Checks if a resolved server exists in the database for the given domain.
     *
     * @param string $domain The domain to check in the resolved_servers table.
     * @return bool True if the server exists in the database, otherwise false.
     * @throws DatabaseOperationException If there is an error during the database operation.
     */
    public static function resolvedServerExists(string $domain): bool
    {
        try
        {
            $statement = Database::getConnection()->prepare("SELECT COUNT(*) FROM resolved_servers WHERE domain=?");
            $statement->bindParam(1, $domain);
            $statement->execute();
            return $statement->fetchColumn() > 0;
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to check if a resolved server exists in the database', $e);
        }
    }

    /**
     * Deletes a resolved server from the database.
     *
     * @param string $domain The domain name of the server to be deleted.
     * @return void
     * @throws DatabaseOperationException If the deletion operation fails.
     */
    public static function deleteResolvedServer(string $domain): void
    {
        try
        {
            $statement = Database::getConnection()->prepare("DELETE FROM resolved_servers WHERE domain=?");
            $statement->bindParam(1, $domain);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to delete a resolved server from the database', $e);
        }
    }

    /**
     * Retrieves the last updated date of a resolved server based on its domain.
     *
     * @param string $domain The domain of the resolved server.
     * @return DateTime The last updated date and time of the resolved server.
     */
    public static function getResolvedServerUpdated(string $domain): DateTime
    {
        try
        {
            $statement = Database::getConnection()->prepare("SELECT updated FROM resolved_servers WHERE domain=?");
            $statement->bindParam(1, $domain);
            $statement->execute();
            $result = $statement->fetchColumn();
            return new DateTime($result);
        }
        catch(Exception $e)
        {
            throw new DatabaseOperationException('Failed to get the updated date of a resolved server from the database', $e);
        }
    }

    /**
     * Retrieves the resolved server record from the database for a given domain.
     *
     * @param string $domain The domain name for which to retrieve the resolved server record.
     * @return ResolvedServerRecord|null The resolved server record associated with the given domain.
     * @throws DatabaseOperationException If there is an error retrieving the resolved server record from the database.
     * @throws \DateMalformedStringException If the date string is malformed.
     */
    public static function getResolvedServer(string $domain): ?ResolvedServerRecord
    {
        try
        {
            $statement = Database::getConnection()->prepare("SELECT * FROM resolved_servers WHERE domain=?");
            $statement->bindParam(1, $domain);
            $statement->execute();
            $result = $statement->fetch();

            if($result === false)
            {
                return null;
            }

            return ResolvedServerRecord::fromArray($result);
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to get a resolved server from the database', $e);
        }
    }

    /**
     * Adds or updates a resolved server in the database.
     *
     * @param string $domain The domain name of the resolved server.
     * @param ResolvedServer $resolvedServer The resolved server object containing endpoint and public key.
     * @return void
     * @throws DatabaseOperationException If a database operation fails.
     */
    public static function addResolvedServer(string $domain, ResolvedServer $resolvedServer): void
    {
        $endpoint = $resolvedServer->getEndpoint();
        $publicKey = $resolvedServer->getPublicKey();

        if(self::resolvedServerExists($domain))
        {
            try
            {
                $statement = Database::getConnection()->prepare("UPDATE resolved_servers SET endpoint=?, public_key=?, updated=NOW() WHERE domain=?");
                $statement->bindParam(1, $endpoint);
                $statement->bindParam(2, $publicKey);
                $statement->bindParam(3, $domain);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update a resolved server in the database', $e);
            }
        }

        try
        {
            $statement = Database::getConnection()->prepare("INSERT INTO resolved_servers (domain, endpoint, public_key) VALUES (?, ?, ?)");
            $statement->bindParam(1, $domain);
            $statement->bindParam(2, $endpoint);
            $statement->bindParam(3, $publicKey);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to add a resolved server to the database', $e);
        }
    }
}