<?php

namespace Socialbox\Managers;

use PDO;
use PDOException;
use Socialbox\Classes\Configuration;
use Socialbox\Classes\Database;
use Socialbox\Classes\Logger;
use Socialbox\Enums\Flags\PeerFlags;
use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Objects\Database\RegisteredPeerRecord;
use Socialbox\Objects\Database\SecurePasswordRecord;
use Symfony\Component\Uid\Uuid;

class RegisteredPeerManager
{
    /**
     * Checks if a username already exists in the database.
     *
     * @param string $username The username to check.
     * @return bool True if the username exists, false otherwise.
     * @throws DatabaseOperationException If the operation fails.
     */
    public static function usernameExists(string $username): bool
    {
        Logger::getLogger()->verbose(sprintf("Checking if username %s already exists", $username));

        try
        {
            $statement = Database::getConnection()->prepare('SELECT COUNT(*) FROM `registered_peers` WHERE username=?');
            $statement->bindParam(1, $username);
            $statement->execute();

            $result = $statement->fetchColumn();
            return $result > 0;
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to check if the username exists', $e);
        }
    }

    /**
     * Creates a new peer with the given username.
     *
     * @param string $username The username to associate with the new peer.
     * @param bool $enabled True if the peer should be enabled, false otherwise.
     * @return string The UUID of the newly created peer.
     * @throws DatabaseOperationException If the operation fails.
     */
    public static function createPeer(string $username, bool $enabled=false): string
    {
        Logger::getLogger()->verbose(sprintf("Creating a new peer with username %s", $username));
        $uuid = Uuid::v4()->toRfc4122();

        try
        {
            $statement = Database::getConnection()->prepare('INSERT INTO `registered_peers` (uuid, username, enabled) VALUES (?, ?, ?)');
            $statement->bindParam(1, $uuid);
            $statement->bindParam(2, $username);
            $statement->bindParam(3, $enabled, PDO::PARAM_BOOL);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to create the peer in the database', $e);
        }

        return $uuid;
    }

    /**
     * Deletes a peer from the database based on the given UUID or RegisteredPeerRecord.
     * WARNING: This operation is cascading and will delete all associated data.
     *
     * @param string|RegisteredPeerRecord $uuid The UUID or RegisteredPeerRecord instance representing the peer to be deleted.
     * @return void
     * @throws DatabaseOperationException If the operation fails.
     */
    public static function deletePeer(string|RegisteredPeerRecord $uuid): void
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

        Logger::getLogger()->verbose(sprintf("Deleting peer %s", $uuid));

        try
        {
            $statement = Database::getConnection()->prepare('DELETE FROM `registered_peers` WHERE uuid=?');
            $statement->bindParam(1, $uuid);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to delete the peer from the database', $e);
        }
    }

    /**
     * Retrieves a registered peer record based on the given unique identifier or RegisteredPeerRecord object.
     *
     * @param string|RegisteredPeerRecord $uuid The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
     * @return RegisteredPeerRecord Returns a RegisteredPeerRecord object containing the peer's information.
     * @throws DatabaseOperationException If there is an error during the database operation.
     */
    public static function getPeer(string|RegisteredPeerRecord $uuid): RegisteredPeerRecord
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

        Logger::getLogger()->verbose(sprintf("Retrieving peer %s from the database", $uuid));

        try
        {
            $statement = Database::getConnection()->prepare('SELECT * FROM `registered_peers` WHERE uuid=?');
            $statement->bindParam(1, $uuid);
            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if($result === false)
            {
                throw new DatabaseOperationException(sprintf("The requested peer '%s' does not exist", $uuid));
            }

            return new RegisteredPeerRecord($result);
        }
        catch(PDOException | \DateMalformedStringException $e)
        {
            throw new DatabaseOperationException('Failed to get the peer from the database', $e);
        }
    }

    /**
     * Retrieves a peer record by the given username.
     *
     * @param string $username The username of the peer to be retrieved.
     * @return RegisteredPeerRecord|null The record of the peer associated with the given username.
     * @throws DatabaseOperationException If there is an error while querying the database.
     */
    public static function getPeerByUsername(string $username): ?RegisteredPeerRecord
    {
        Logger::getLogger()->verbose(sprintf("Retrieving peer %s from the database", $username));

        try
        {
            $statement = Database::getConnection()->prepare('SELECT * FROM `registered_peers` WHERE username=?');
            $statement->bindParam(1, $username);
            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if($result === false)
            {
                return null;
            }

            return new RegisteredPeerRecord($result);
        }
        catch(PDOException | \DateMalformedStringException $e)
        {
            throw new DatabaseOperationException('Failed to get the peer from the database', $e);
        }
    }

    /**
     * Enables a peer identified by the given UUID or RegisteredPeerRecord.
     *
     * @param string|RegisteredPeerRecord $uuid The UUID or RegisteredPeerRecord instance representing the peer to be enabled.
     * @return void
     * @throws DatabaseOperationException If there is an error while updating the database.
     */
    public static function enablePeer(string|RegisteredPeerRecord $uuid): void
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

        Logger::getLogger()->verbose(sprintf("Enabling peer %s", $uuid));

        try
        {
            $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET enabled=1 WHERE uuid=?');
            $statement->bindParam(1, $uuid);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to enable the peer in the database', $e);
        }
    }

    /**
     * Disables the peer identified by the given UUID or RegisteredPeerRecord.
     *
     * @param string|RegisteredPeerRecord $uuid The UUID or RegisteredPeerRecord instance representing the peer.
     * @return void
     * @throws DatabaseOperationException If there is an error while updating the peer's status in the database.
     */
    public static function disablePeer(string|RegisteredPeerRecord $uuid): void
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

        Logger::getLogger()->verbose(sprintf("Disabling peer %s", $uuid));

        try
        {
            $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET enabled=0 WHERE uuid=?');
            $statement->bindParam(1, $uuid);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to disable the peer in the database', $e);
        }
    }

    /**
     * Adds a specific flag to the peer identified by the given UUID or RegisteredPeerRecord.
     *
     * @param string|RegisteredPeerRecord $uuid The UUID or RegisteredPeerRecord instance representing the peer.
     * @param PeerFlags|array $flags The flag or array of flags to be added to the peer.
     * @return void
     * @throws DatabaseOperationException If there is an error while updating the database.
     */
    public static function addFlag(string|RegisteredPeerRecord $uuid, PeerFlags|array $flags): void
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

        Logger::getLogger()->verbose(sprintf("Adding flag(s) %s to peer %s", implode(',', $flags), $uuid));

        $peer = self::getPeer($uuid);
        $existingFlags = $peer->getFlags();
        $flags = is_array($flags) ? $flags : [$flags];

        foreach($flags as $flag)
        {
            if(!in_array($flag, $existingFlags))
            {
                $existingFlags[] = $flag;
            }
        }

        try
        {
            $implodedFlags = implode(',', array_map(fn($flag) => $flag->name, $existingFlags));
            $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET flags=? WHERE uuid=?');
            $statement->bindParam(1, $implodedFlags);
            $statement->bindParam(2, $uuid);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to add the flag to the peer in the database', $e);
        }
    }

    /**
     * Removes a specific flag from the peer identified by the given UUID or RegisteredPeerRecord.
     *
     * @param string|RegisteredPeerRecord $peer
     * @param PeerFlags $flag The flag to be removed from the peer.
     * @return void
     * @throws DatabaseOperationException If there is an error while updating the database.
     */
    public static function removeFlag(string|RegisteredPeerRecord $peer, PeerFlags $flag): void
    {
        if(is_string($peer))
        {
            $peer = self::getPeer($peer);
        }

        Logger::getLogger()->verbose(sprintf("Removing flag %s from peer %s", $flag->value, $peer->getUuid()));

        if(!$peer->flagExists($flag))
        {
            return;
        }

        $peer->removeFlag($flag);

        try
        {
            $implodedFlags = PeerFlags::toString($peer->getFlags());
            $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET flags=? WHERE uuid=?');
            $statement->bindParam(1, $implodedFlags);
            $statement->bindParam(2, $registeredPeer);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to remove the flag from the peer in the database', $e);
        }
    }

    /**
     *
     */
    public static function getPasswordAuthentication(string|RegisteredPeerRecord $peerUuid): ?SecurePasswordRecord
    {
        if($peerUuid instanceof RegisteredPeerRecord)
        {
            $peerUuid = $peerUuid->getUuid();
        }

        try
        {
            $statement = Database::getConnection()->prepare('SELECT * FROM `authentication_passwords` WHERE peer_uuid=?');
            $statement->bindParam(1, $peerUuid);
            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if($result === false)
            {
                return null;
            }

            return new SecurePasswordRecord($result);
        }
        catch(PDOException | \DateMalformedStringException $e)
        {
            throw new DatabaseOperationException('Failed to get the secure password record from the database', $e);
        }
    }
}