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
    use Socialbox\Enums\Flags\PeerFlags;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\PeerDatabaseRecord;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\Standard\Peer;
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
            Logger::getLogger()->debug(sprintf("Checking if username %s already exists", $username));

            try
            {
                $statement = Database::getConnection()->prepare("SELECT COUNT(*) FROM peers WHERE username=:username AND server='host'");
                $statement->bindParam(':username', $username);
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
            Logger::getLogger()->debug(sprintf("Registering peer %s", $peerAddress->getAddress()));
            $uuid = Uuid::v4()->toRfc4122();
            $server = $peerAddress->getDomain();

            if($server === Configuration::getInstanceConfiguration()->getDomain())
            {
                $server = 'host';
            }

            try
            {
                $statement = Database::getConnection()->prepare('INSERT INTO peers (uuid, username, server, enabled) VALUES (?, ?, ?, ?)');
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
         * @param string|PeerDatabaseRecord $ppeerUuid The UUID or RegisteredPeerRecord instance representing the peer to be deleted.
         * @return void
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function deletePeer(string|PeerDatabaseRecord $ppeerUuid): void
        {
            if($ppeerUuid instanceof PeerDatabaseRecord)
            {
                $ppeerUuid = $ppeerUuid->getUuid();
            }

            Logger::getLogger()->debug(sprintf("Deleting peer %s", $ppeerUuid));

            try
            {
                $statement = Database::getConnection()->prepare('DELETE FROM peers WHERE uuid=?');
                $statement->bindParam(1, $ppeerUuid);
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
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier of the registered peer, or an instance of RegisteredPeerRecord.
         * @return PeerDatabaseRecord|null Returns a RegisteredPeerRecord object containing the peer's information. null if the peer does not exist.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function getPeer(string|PeerDatabaseRecord $peerUuid): ?PeerDatabaseRecord
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            Logger::getLogger()->debug(sprintf("Retrieving peer %s from the database", $peerUuid));

            try
            {
                $statement = Database::getConnection()->prepare('SELECT * FROM peers WHERE uuid=?');
                $statement->bindParam(1, $peerUuid);
                $statement->execute();

                $result = $statement->fetch(PDO::FETCH_ASSOC);

                if($result === false)
                {
                    return null;
                }

                return new PeerDatabaseRecord($result);
            }
            catch(Exception $e)
            {
                throw new DatabaseOperationException('Failed to get the peer from the database', $e);
            }
        }

        /**
         * Retrieves a peer record by the given username.
         *
         * @param PeerAddress $oeerAddress The address of the peer to be retrieved.
         * @return PeerDatabaseRecord|null The record of the peer associated with the given username.
         * @throws DatabaseOperationException If there is an error while querying the database.
         */
        public static function getPeerByAddress(PeerAddress $oeerAddress): ?PeerDatabaseRecord
        {
            Logger::getLogger()->debug(sprintf("Retrieving peer %s from the database", $oeerAddress->getAddress()));

            try
            {
                $statement = Database::getConnection()->prepare('SELECT * FROM peers WHERE username=:username AND server=:server');
                $username = $oeerAddress->getUsername();
                $statement->bindParam(':username', $username);
                $server = $oeerAddress->getDomain();

                // Convert to 'host' if the domain is the same as the server's host
                if($server === Configuration::getInstanceConfiguration()->getDomain())
                {
                    $server = 'host';
                }

                $statement->bindParam(':server', $server);
                $statement->execute();

                $result = $statement->fetch(PDO::FETCH_ASSOC);

                if($result === false)
                {
                    Logger::getLogger()->debug(sprintf("Peer %s not found in the database", $oeerAddress->getAddress()));
                    return null;
                }

                return new PeerDatabaseRecord($result);
            }
            catch(Exception $e)
            {
                throw new DatabaseOperationException('Failed to get the peer from the database', $e);
            }
        }

        /**
         * Synchronizes the provided external peer by adding its details to the registered peers in the database.
         *
         * @param Peer $peer The peer object representing the external peer to be synchronized.
         * @return void This method does not return any value.
         * @throws InvalidArgumentException If the given peer is not an external peer or if it represents a host peer.
         * @throws DatabaseOperationException If there is an error during the database operation to insert the peer's details.
         */
        public static function synchronizeExternalPeer(Peer $peer): void
        {
            if($peer->getPeerAddress()->getDomain() === Configuration::getInstanceConfiguration()->getDomain())
            {
                throw new InvalidArgumentException('Given peer is not an external peer');
            }

            if($peer->getPeerAddress()->getUsername() === ReservedUsernames::HOST->value)
            {
                throw new InvalidArgumentException('Cannot synchronize an external host peer');
            }

            $existingPeer = self::getPeerByAddress($peer->getPeerAddress());
            if($existingPeer !== null)
            {
                // getUpdated is DateTime() if it's older than 1 hour, update it
                if($existingPeer->getUpdated()->diff(new DateTime())->h < 1)
                {
                    return;
                }

                try
                {
                    $statement = Database::getConnection()->prepare('UPDATE peers SET updated=? WHERE uuid=?');
                    $updated = new DateTime();
                    $statement->bindParam(':updated', $updated);
                    $uuid = $existingPeer->getUuid();
                    $statement->bindParam(':uuid', $uuid);
                    $statement->execute();
                }
                catch(PDOException $e)
                {
                    throw new DatabaseOperationException('Failed to update the external peer in the database', $e);
                }

                foreach($peer->getInformationFields() as $informationField)
                {
                    try
                    {
                        if(PeerInformationManager::fieldExists($existingPeer, $informationField->getName()))
                        {
                            PeerInformationManager::updateField($existingPeer, $informationField->getName(), $informationField->getValue());
                        }
                        else
                        {
                            PeerInformationManager::addField($existingPeer, $informationField->getName(), $informationField->getValue(), PrivacyState::PUBLIC);
                        }
                    }
                    catch(DatabaseOperationException $e)
                    {
                        throw new DatabaseOperationException('Failed to update the external peer information in the database', $e);
                    }
                }

                return;
            }

            $uuid = Uuid::v4()->toRfc4122();

            try
            {
                $statement = Database::getConnection()->prepare('INSERT INTO peers (uuid, username, server, enabled) VALUES (:uuid, :username, :server, 1)');
                $statement->bindParam(':uuid', $uuid);
                $username = $peer->getPeerAddress()->getUsername();
                $statement->bindParam(':username', $username);
                $server = $peer->getPeerAddress()->getDomain();
                $statement->bindParam(':server', $server);

                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to synchronize the external peer in the database', $e);
            }

            foreach($peer->getInformationFields() as $informationField)
            {
                try
                {
                    PeerInformationManager::addField($uuid, $informationField->getName(), $informationField->getValue(), PrivacyState::PUBLIC);
                }
                catch(DatabaseOperationException $e)
                {
                    throw new DatabaseOperationException('Failed to add the external peer information in the database', $e);
                }
            }
        }

        /**
         * Enables a peer identified by the given UUID or RegisteredPeerRecord.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID or RegisteredPeerRecord instance representing the peer to be enabled.
         * @return void
         * @throws DatabaseOperationException If there is an error while updating the database.
         */
        public static function enablePeer(string|PeerDatabaseRecord $peerUuid): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            Logger::getLogger()->verbose(sprintf("Enabling peer %s", $peerUuid));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE peers SET enabled=1 WHERE uuid=?');
                $statement->bindParam(1, $peerUuid);
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
         * @param string|PeerDatabaseRecord $peerUuid The UUID or RegisteredPeerRecord instance representing the peer.
         * @return void
         * @throws DatabaseOperationException If there is an error while updating the peer's status in the database.
         */
        public static function disablePeer(string|PeerDatabaseRecord $peerUuid): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            Logger::getLogger()->verbose(sprintf("Disabling peer %s", $peerUuid));

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE peers SET enabled=0 WHERE uuid=?');
                $statement->bindParam(1, $peerUuid);
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
         * @param string|PeerDatabaseRecord $peerUuid The UUID or RegisteredPeerRecord instance representing the peer.
         * @param PeerFlags|array $peerFlags The flag or array of flags to be added to the peer.
         * @return void
         * @throws DatabaseOperationException If there is an error while updating the database.
         */
        public static function addFlag(string|PeerDatabaseRecord $peerUuid, PeerFlags|array $peerFlags): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            Logger::getLogger()->verbose(sprintf("Adding flag(s) %s to peer %s", implode(',', $peerFlags), $peerUuid));

            $peer = self::getPeer($peerUuid);
            if($peer === null)
            {
                throw new DatabaseOperationException('Peer does not exist');
            }

            $existingFlags = $peer->getFlags();
            $peerFlags = is_array($peerFlags) ? $peerFlags : [$peerFlags];

            foreach($peerFlags as $flag)
            {
                if(!in_array($flag, $existingFlags))
                {
                    $existingFlags[] = $flag;
                }
            }

            try
            {
                $implodedFlags = implode(',', array_map(fn($flag) => $flag->name, $existingFlags));
                $statement = Database::getConnection()->prepare('UPDATE peers SET flags=? WHERE uuid=?');
                $statement->bindParam(1, $implodedFlags);
                $statement->bindParam(2, $peerUuid);
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
         * @param string|PeerDatabaseRecord $peer
         * @param PeerFlags $flag The flag to be removed from the peer.
         * @return void
         * @throws DatabaseOperationException If there is an error while updating the database.
         */
        public static function removeFlag(string|PeerDatabaseRecord $peer, PeerFlags $flag): void
        {
            if(is_string($peer))
            {
                $peer = self::getPeer($peer);
            }

            if($peer === null)
            {
                throw new DatabaseOperationException('Peer does not exist');
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
                $statement = Database::getConnection()->prepare('UPDATE peers SET flags=? WHERE uuid=?');
                $statement->bindParam(1, $implodedFlags);
                $statement->bindParam(2, $registeredPeer);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to remove the flag from the peer in the database', $e);
            }
        }
    }