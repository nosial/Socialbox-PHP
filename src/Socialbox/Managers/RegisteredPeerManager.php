<?php

namespace Socialbox\Managers;

use PDO;
use PDOException;
use Socialbox\Classes\Configuration;
use Socialbox\Classes\Database;
use Socialbox\Enums\Flags\PeerFlags;
use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Objects\Database\RegisteredPeerRecord;
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
        if(self::usernameExists($username))
        {
            throw new DatabaseOperationException('The username already exists');
        }

        $uuid = Uuid::v4()->toRfc4122();

        // If `enabled` is True, we insert the peer into the database as an activated account.
        if($enabled)
        {
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

        // Otherwise, we insert the peer into the database as a disabled account & the required verification flags.
        $flags = [];

        if(Configuration::getRegistrationConfiguration()->isRegistrationEnabled())
        {
            $flags[] = PeerFlags::VER_EMAIL;
        }

        if(Configuration::getRegistrationConfiguration()->isPasswordRequired())
        {
            $flags[] = PeerFlags::VER_SET_PASSWORD;
        }

        if(Configuration::getRegistrationConfiguration()->isOtpRequired())
        {
            $flags[] = PeerFlags::VER_SET_OTP;
        }

        if(Configuration::getRegistrationConfiguration()->isDisplayNameRequired())
        {
            $flags[] = PeerFlags::VER_SET_DISPLAY_NAME;
        }

        if(Configuration::getRegistrationConfiguration()->isEmailVerificationRequired())
        {
            $flags[] = PeerFlags::VER_EMAIL;
        }

        if(Configuration::getRegistrationConfiguration()->isSmsVerificationRequired())
        {
            $flags[] = PeerFlags::VER_SMS;
        }

        if(Configuration::getRegistrationConfiguration()->isPhoneCallVerificationRequired())
        {
            $flags[] = PeerFlags::VER_PHONE_CALL;
        }

        if(Configuration::getRegistrationConfiguration()->isImageCaptchaVerificationRequired())
        {
            $flags[] = PeerFlags::VER_SOLVE_IMAGE_CAPTCHA;
        }

        try
        {
            $implodedFlags = implode(',', $flags);
            $statement = Database::getConnection()->prepare('INSERT INTO `registered_peers` (uuid, username, enabled, flags) VALUES (?, ?, ?, ?)');
            $statement->bindParam(1, $uuid);
            $statement->bindParam(2, $username);
            $statement->bindParam(3, $enabled, PDO::PARAM_BOOL);
            $statement->bindParam(4, $implodedFlags);
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
     * @throws StandardException If the requested peer does not exist.
     * @throws DatabaseOperationException If there is an error during the database operation.
     */
    public static function getPeer(string|RegisteredPeerRecord $uuid): RegisteredPeerRecord
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

        try
        {
            $statement = Database::getConnection()->prepare('SELECT * FROM `registered_peers` WHERE uuid=?');
            $statement->bindParam(1, $uuid);
            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if($result === false)
            {
                throw new StandardException(sprintf("The requested peer '%s' does not exist", $uuid), StandardError::PEER_NOT_FOUND);
            }

            return new RegisteredPeerRecord($result);
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to get the peer from the database', $e);
        }
    }

    /**
     * Retrieves a peer record by the given username.
     *
     * @param string $username The username of the peer to be retrieved.
     * @return RegisteredPeerRecord The record of the peer associated with the given username.
     * @throws DatabaseOperationException If there is an error while querying the database.
     * @throws StandardException If the peer does not exist.
     */
    public static function getPeerByUsername(string $username): RegisteredPeerRecord
    {
        try
        {
            $statement = Database::getConnection()->prepare('SELECT * FROM `registered_peers` WHERE username=?');
            $statement->bindParam(1, $username);
            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if($result === false)
            {
                throw new StandardException(sprintf("The requested peer '%s' does not exist", $username), StandardError::PEER_NOT_FOUND);
            }

            return new RegisteredPeerRecord($result);
        }
        catch(PDOException $e)
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
     * @throws StandardException If the peer does not exist.
     */
    public static function addFlag(string|RegisteredPeerRecord $uuid, PeerFlags|array $flags): void
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

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
     * @param string|RegisteredPeerRecord $uuid The UUID or RegisteredPeerRecord instance representing the peer.
     * @param PeerFlags $flag The flag to be removed from the peer.
     * @return void
     * @throws DatabaseOperationException If there is an error while updating the database.
     * @throws StandardException If the peer does not exist.
     */
    public static function removeFlag(string|RegisteredPeerRecord $uuid, PeerFlags $flag): void
    {
        if($uuid instanceof RegisteredPeerRecord)
        {
            $uuid = $uuid->getUuid();
        }

        $peer = self::getPeer($uuid);
        if(!$peer->flagExists($flag))
        {
            return;
        }

        $flags = $peer->getFlags();
        unset($flags[array_search($flag, $flags)]);

        try
        {
            $implodedFlags = implode(',', $flags);
            $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET flags=? WHERE uuid=?');
            $statement->bindParam(1, $implodedFlags);
            $statement->bindParam(2, $uuid);
            $statement->execute();
        }
        catch(PDOException $e)
        {
            throw new DatabaseOperationException('Failed to remove the flag from the peer in the database', $e);
        }
    }
}