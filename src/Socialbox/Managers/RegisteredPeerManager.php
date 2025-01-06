<?php

    namespace Socialbox\Managers;

    use DateTime;
    use Exception;
    use InvalidArgumentException;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Logger;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\Flags\PeerFlags;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\RegisteredPeerRecord;
    use Socialbox\Objects\PeerAddress;
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
         * @param PeerAddress $peerAddress The address of the peer to be created.
         * @param bool $enabled True if the peer should be enabled, false otherwise.
         * @return string The UUID of the newly created peer.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function createPeer(PeerAddress $peerAddress, bool $enabled=false): string
        {
            Logger::getLogger()->verbose(sprintf("Registering peer %s", $peerAddress->getAddress()));
            $uuid = Uuid::v4()->toRfc4122();
            $server = $peerAddress->getDomain();

            if($server === Configuration::getInstanceConfiguration()->getDomain())
            {
                $server = 'host';
            }

            try
            {
                $statement = Database::getConnection()->prepare('INSERT INTO `registered_peers` (uuid, username, server, enabled) VALUES (?, ?, ?, ?)');
                $statement->bindParam(1, $uuid);
                $username = $peerAddress->getUsername();
                $statement->bindParam(2, $username);
                $statement->bindParam(3, $server);
                $statement->bindParam(4, $enabled, PDO::PARAM_BOOL);
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
            catch(Exception $e)
            {
                throw new DatabaseOperationException('Failed to get the peer from the database', $e);
            }
        }

        /**
         * Retrieves a peer record by the given username.
         *
         * @param PeerAddress $address The address of the peer to be retrieved.
         * @return RegisteredPeerRecord|null The record of the peer associated with the given username.
         * @throws DatabaseOperationException If there is an error while querying the database.
         */
        public static function getPeerByAddress(PeerAddress $address): ?RegisteredPeerRecord
        {
            Logger::getLogger()->verbose(sprintf("Retrieving peer %s from the database", $address->getAddress()));

            try
            {
                $statement = Database::getConnection()->prepare('SELECT * FROM `registered_peers` WHERE username=? AND server=?');
                $username = $address->getUsername();
                $statement->bindParam(1, $username);
                $server = $address->getDomain();
                $statement->bindParam(2, $server);
                $statement->execute();

                $result = $statement->fetch(PDO::FETCH_ASSOC);

                if($result === false)
                {
                    return null;
                }

                return new RegisteredPeerRecord($result);
            }
            catch(Exception $e)
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
         * Updates the display name of a registered peer based on the given unique identifier or RegisteredPeerRecord object.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @param string $name The new display name to set to the user
         * @throws DatabaseOperationException Thrown if there was an error while trying to update the display name
         */
        public static function updateDisplayName(string|RegisteredPeerRecord $peer, string $name): void
        {
            if(empty($name))
            {
                throw new InvalidArgumentException('The display name cannot be empty');
            }

            if(strlen($name) > 256)
            {
                throw new InvalidArgumentException('The display name cannot exceed 256 characters');
            }

            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot update the display name of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Updating display name of peer %s to %s", $peer->getUuid(), $name));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET display_name=? WHERE uuid=?');
                $statement->bindParam(1, $name);
                $uuid = $peer->getUuid();
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the display name of the peer in the database', $e);
            }
        }

        /**
         * Deletes the display name of a registered peer identified by a unique identifier or RegisteredPeerRecord object.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @return void
         * @throws InvalidArgumentException If the peer is external and its display name cannot be deleted.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function deleteDisplayName(string|RegisteredPeerRecord $peer): void
        {
            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot delete the display name of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Deleting display name of peer %s", $peer->getUuid()));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET display_name=NULL WHERE uuid=?');
                $uuid = $peer->getUuid();
                $statement->bindParam(1, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete the display name of the peer in the database', $e);
            }
        }

        /**
         * Updates the display picture of a registered peer in the database.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the peer or an instance of RegisteredPeerRecord.
         * @param string $displayPictureData The raw jpeg data of the display picture.
         * @return void
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function updateDisplayPicture(string|RegisteredPeerRecord $peer, string $displayPictureData): void
        {
            if(empty($uuid))
            {
                throw new InvalidArgumentException('The display picture UUID cannot be empty');
            }

            $uuid = Uuid::v4()->toRfc4122();
            $displayPicturePath = Configuration::getStorageConfiguration()->getUserDisplayImagesPath() . DIRECTORY_SEPARATOR . $uuid . '.jpeg';

            // Delete the file if it already exists
            if(file_exists($displayPicturePath))
            {
                unlink($displayPicturePath);
            }

            // Write the file contents & set the permissions
            file_put_contents($displayPicturePath, $displayPictureData);
            chmod($displayPicturePath, 0644);

            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            // TODO: Handle for external peers, needs a way to resolve peers to their external counterparts
            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot update the display picture of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Updating display picture of peer %s to %s", $peer->getUuid(), $uuid));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET display_picture=? WHERE uuid=?');
                $statement->bindParam(1, $uuid);
                $peerUuid = $peer->getUuid();
                $statement->bindParam(2, $peerUuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the display picture of the peer in the database', $e);
            }
        }

        /**
         * Deletes the display picture of a registered peer based on the given unique identifier or RegisteredPeerRecord object.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @return void
         * @throws InvalidArgumentException If the peer is external and its display picture cannot be deleted.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function deleteDisplayPicture(string|RegisteredPeerRecord $peer): void
        {
            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                // TODO: Implement this
                throw new InvalidArgumentException('Cannot delete the display picture of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Deleting display picture of peer %s", $peer->getUuid()));

            // Delete the file if it exists
            $displayPicturePath = Configuration::getStorageConfiguration()->getUserDisplayImagesPath() . DIRECTORY_SEPARATOR . $peer->getDisplayPicture() . '.jpeg';
            if(file_exists($displayPicturePath))
            {
                unlink($displayPicturePath);
            }

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET display_picture=NULL WHERE uuid=?');
                $uuid = $peer->getUuid();
                $statement->bindParam(1, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete the display picture of the peer in the database', $e);
            }
        }

        /**
         * Updates the email address of a registered peer.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the peer, or an instance of RegisteredPeerRecord.
         * @param string $emailAddress The new email address to be assigned to the peer.
         * @return void
         * @throws InvalidArgumentException If the email address is empty, exceeds 256 characters, is not a valid email format, or if the peer is external.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function updateEmailAddress(string|RegisteredPeerRecord $peer, string $emailAddress): void
        {
            if(empty($emailAddress))
            {
                throw new InvalidArgumentException('The email address cannot be empty');
            }

            if(strlen($emailAddress) > 256)
            {
                throw new InvalidArgumentException('The email address cannot exceed 256 characters');
            }

            if(filter_var($emailAddress, FILTER_VALIDATE_EMAIL) === false)
            {
                throw new InvalidArgumentException('The email address is not valid');
            }

            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot update the email address of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Updating email address of peer %s to %s", $peer->getUuid(), $emailAddress));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET email_address=? WHERE uuid=?');
                $statement->bindParam(1, $emailAddress);
                $uuid = $peer->getUuid();
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the email address of the peer in the database', $e);
            }
        }

        /**
         * Deletes the email address of a registered peer identified by either a unique identifier or a RegisteredPeerRecord object.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @return void
         * @throws InvalidArgumentException If the peer is external and its email address cannot be deleted.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function deleteEmailAddress(string|RegisteredPeerRecord $peer): void
        {
            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot delete the email address of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Deleting email address of peer %s", $peer->getUuid()));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET email_address=NULL WHERE uuid=?');
                $uuid = $peer->getUuid();
                $statement->bindParam(1, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete the email address of the peer in the database', $e);
            }
        }

        /**
         * Updates the phone number of the specified registered peer.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @param string $phoneNumber The new phone number to be set for the peer.
         * @return void
         * @throws InvalidArgumentException If the phone number is empty, exceeds 16 characters, is invalid, or if the peer is external.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function updatePhoneNumber(string|RegisteredPeerRecord $peer, string $phoneNumber): void
        {
            if(empty($phoneNumber))
            {
                throw new InvalidArgumentException('The phone number cannot be empty');
            }

            if(strlen($phoneNumber) > 16)
            {
                throw new InvalidArgumentException('The phone number cannot exceed 16 characters');
            }
            
            if(!Validator::validatePhoneNumber($phoneNumber))
            {
                throw new InvalidArgumentException('The phone number is not valid');
            }

            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot update the phone number of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Updating phone number of peer %s to %s", $peer->getUuid(), $phoneNumber));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET phone_number=? WHERE uuid=?');
                $statement->bindParam(1, $phoneNumber);
                $uuid = $peer->getUuid();
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the phone number of the peer in the database', $e);
            }
        }

        /**
         * Deletes the phone number of a registered peer based on the given unique identifier or RegisteredPeerRecord object.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @return void This method does not return a value.
         * @throws InvalidArgumentException If the peer is external and its phone number cannot be deleted.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function deletePhoneNumber(string|RegisteredPeerRecord $peer): void
        {
            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot delete the phone number of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Deleting phone number of peer %s", $peer->getUuid()));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET phone_number=NULL WHERE uuid=?');
                $uuid = $peer->getUuid();
                $statement->bindParam(1, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete the phone number of the peer in the database', $e);
            }
        }

        /**
         * Sets the birthday of a registered peer record based on the provided date components.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the peer or an instance of RegisteredPeerRecord.
         * @param int $year The year component of the birthday.
         * @param int $month The month component of the birthday.
         * @param int $day The day component of the birthday.
         * @return void
         * @throws InvalidArgumentException If the peer is external or the provided date is invalid.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function setBirthday(string|RegisteredPeerRecord $peer, int $year, int $month, int $day): void
        {
            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot set the birthday of an external peer');
            }

            if(!Validator::validateDate($month, $day, $year))
            {
                throw new InvalidArgumentException('The provided date is not valid');
            }

            Logger::getLogger()->verbose(sprintf("Setting birthday of peer %s to %d-%d-%d", $peer->getUuid(), $year, $month, $day));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET birthday=? WHERE uuid=?');
                $birthday = (new DateTime())->setDate($year, $month, $day)->format('Y-m-d');
                $statement->bindParam(1, $birthday);
                $uuid = $peer->getUuid();
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to set the birthday of the peer in the database', $e);
            }
        }

        /**
         * Deletes the birthday of a registered peer based on the given unique identifier or RegisteredPeerRecord object.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @throws InvalidArgumentException If the peer is marked as external and cannot have its birthday deleted.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function deleteBirthday(string|RegisteredPeerRecord $peer): void
        {
            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer->isExternal())
            {
                throw new InvalidArgumentException('Cannot delete the birthday of an external peer');
            }

            Logger::getLogger()->verbose(sprintf("Deleting birthday of peer %s", $peer->getUuid()));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE `registered_peers` SET birthday=NULL WHERE uuid=?');
                $uuid = $peer->getUuid();
                $statement->bindParam(1, $uuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete the birthday of the peer in the database', $e);
            }
        }
    }