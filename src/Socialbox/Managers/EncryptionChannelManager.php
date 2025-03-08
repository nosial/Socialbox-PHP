<?php


    namespace Socialbox\Managers;


    use InvalidArgumentException;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\Status\EncryptionChannelStatus;
    use Socialbox\Enums\Types\EncryptionMessageRecipient;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\EncryptionChannelMessageRecord;
    use Socialbox\Objects\Database\EncryptionChannelRecord;
    use Socialbox\Objects\PeerAddress;
    use Symfony\Component\Uid\Uuid;

    class EncryptionChannelManager
    {
        /**
         * Checks if an encryption channel with the specified UUID exists.
         *
         * @param string $channelUuid The UUID of the channel.
         * @return bool True if the channel exists, false otherwise.
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function channelUuidExists(string $channelUuid): bool
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid channel UUID V4');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM encryption_channels WHERE uuid=:uuid');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();

                return $stmt->fetchColumn() > 0;
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if channel UUID exists', $e);
            }
        }

        /**
         * Deletes an encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel.
         * @return void
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function deleteChannel(string $channelUuid): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid UUID V4');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('DELETE FROM encryption_channels WHERE uuid=:uuid LIMIT 1');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete encryption channel', $e);
            }
        }

        /**
         * Creates a new encryption channel by inserting the caller's request information into the database.
         *
         * @param string|PeerAddress $callingPeer The peer address of the caller.
         * @param string|PeerAddress $receivingPeer The peer address of the receiver.
         * @param string $callingPublicEncryptionKey The public encryption key of the caller.
         * @param string|null $channelUuid The UUID of the channel. If not provided, a new UUID will be generated.
         * @return string The UUID of the created channel.
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function createChannel(string|PeerAddress $callingPeer, string|PeerAddress $receivingPeer,
            string                                              $callingPublicEncryptionKey, ?string $channelUuid=null): string
        {
            if($channelUuid === null)
            {
                $channelUUid = Uuid::v4()->toRfc4122();
            }
            elseif(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid UUID V4');
            }

            if($callingPeer instanceof PeerAddress)
            {
                $callingPeer = $callingPeer->getAddress();
            }
            elseif(!Validator::validatePeerAddress($callingPeer))
            {
                throw new InvalidArgumentException('Invalid calling peer address');
            }

            if($receivingPeer instanceof PeerAddress)
            {
                $receivingPeer = $receivingPeer->getAddress();
            }
            elseif(!Validator::validatePeerAddress($receivingPeer))
            {
                throw new InvalidArgumentException('Invalid receiving peer address');
            }

            if(!Cryptography::validatePublicEncryptionKey($callingPublicEncryptionKey))
            {
                throw new InvalidArgumentException('Invalid public encryption key');
            }

            try
            {
                $channelUuid = $channelUuid ?? Uuid::v4()->toRfc4122();
                $stmt = Database::getConnection()->prepare('INSERT INTO encryption_channels (uuid, calling_peer_address, receiving_peer_address, calling_peer_address, calling_public_encryption_key) VALUES (:uuid, :calling_peer_address, :receiving_peer_address, :calling_peer_address, :calling_public_encryption_key)');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->bindParam(':calling_peer_address', $callingPeer);
                $stmt->bindParam(':receiving_peer_address', $receivingPeer);
                $stmt->bindParam(':calling_public_encryption_key', $callingPublicEncryptionKey);
                $stmt->execute();

                return $channelUuid;
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to create encryption channel', $e);
            }
        }

        /**
         * Declines an encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel.
         * @param bool $isServer True if the server is declining the channel, false if the peer is declining the channel.
         * @return void
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function declineChannel(string $channelUuid, bool $isServer=false): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid UUID V4');
            }

            try
            {
                $status = $isServer ? EncryptionChannelStatus::SERVER_REJECTED->value : EncryptionChannelStatus::PEER_REJECTED->value;

                $stmt = Database::getConnection()->prepare('UPDATE encryption_channels SET status=:status WHERE uuid=:uuid LIMIT 1');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->bindParam(':status', $status);
                $stmt->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to decline encryption channel', $e);
            }
        }

        /**
         * Accepts an incoming channel as the receiver, requires the receiver's generated public encryption key
         * so that both sides can preform a DHE and get the shared secret
         *
         * @param string $channelUuid The Unique Universal Identifier for the channel
         * @param string $publicEncryptionKey The public encryption key of the receiver
         * @return void
         * @throws DatabaseOperationException Thrown if there was a database error while trying to accept the chanel
         */
        public static function acceptChannel(string $channelUuid, string $publicEncryptionKey): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid UUID V4');
            }

            if(!Cryptography::validatePublicEncryptionKey($publicEncryptionKey))
            {
                throw new InvalidArgumentException('Invalid public encryption key');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE encryption_channels SET status=:status, receiving_public_encryption_key=:public_encryption_key WHERE uuid=:uuid LIMIT 1');
                $status = EncryptionChannelStatus::OPENED->value;
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->bindParam(':public_encryption_key', $publicEncryptionKey);
                $stmt->bindParam(':status', $status);
                $stmt->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to decline encryption channel', $e);
            }
        }

        /**
         * Closes an encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel.
         * @return void
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function closeChannel(string $channelUuid): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid Channel UUID V4');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE encryption_channels SET status=:status WHERE uuid=:uuid LIMIT 1');
                $status = EncryptionChannelStatus::CLOSED->value;
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->bindParam(':status', $status);
                $stmt->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to decline encryption channel', $e);
            }
        }

        /**
         * Returns an existing encryption channel from the database.
         *
         * @param string $channelUuid The
         * @return EncryptionChannelRecord|null
         * @throws DatabaseOperationException
         */
        public static function getChannel(string $channelUuid): ?EncryptionChannelRecord
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid Channel UUID V4');
            }

            try

            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE uuid=:uuid LIMIT 1');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();

                if($result = $stmt->fetch())
                {
                    return EncryptionChannelRecord::fromArray($result);
                }
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve encryption channel', $e);
            }

            return null;
        }

        /**
         * Returns an array of channels that are outgoing from the specified peer address
         *
         * @param string|PeerAddress $peerAddress The Peer Address of the caller
         * @param int $page The page number
         * @param int $limit The limit of records to return
         * @return EncryptionChannelRecord[] An array of channel records
         * @throws DatabaseOperationException Thrown if there was a database error while retrieving the records
         */
        public static function getChannels(string|PeerAddress $peerAddress, int $page=1, int $limit=100): array
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }
            elseif(!Validator::validatePeerAddress($peerAddress))
            {
                throw new InvalidArgumentException('Invalid Peer Address');
            }

            if($page < 1)
            {
                throw new InvalidArgumentException('The page number cannot be less than 1');
            }

            if($limit < 1)
            {
                throw new InvalidArgumentException('The limit cannot be less than 1');
            }
            elseif($limit > Configuration::getPoliciesConfiguration()->getEncryptionChannelsLimit())
            {
                throw new InvalidArgumentException(sprintf('The limit cannot exceed a value of %d', Configuration::getPoliciesConfiguration()->getEncryptionChannelsLimit()));
            }

            try
            {
                $offset = ($page - 1) * $limit;
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE calling_peer_address=:peer_address OR receiving_peer_address=:peer_address LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':peer_address', $peerAddress);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();

                $results = $stmt->fetchAll();

                if(!$results)
                {
                    return [];
                }

                return array_map(fn($result) => EncryptionChannelRecord::fromArray($result), $results);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve encryption channels', $e);
            }
        }

        /**
         * Returns an array of channels that are outgoing from the specified peer address
         *
         * @param string|PeerAddress $peerAddress The Peer Address of the caller
         * @param int $page The page number
         * @param int $limit The limit of records to return
         * @return EncryptionChannelRecord[] An array of channel records
         * @throws DatabaseOperationException Thrown if there was a database error while retrieving the records
         */
        public static function getIncomingChannels(string|PeerAddress $peerAddress, int $page=1, int $limit=100): array
        {
            if(is_string($peerAddress) && !Validator::validatePeerAddress($peerAddress))
            {
                throw new InvalidArgumentException('Invalid Peer Address');
            }

            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }

            if($page < 1)
            {
                throw new InvalidArgumentException('The page number cannot be less than 1');
            }

            if($limit < 1)
            {
                throw new InvalidArgumentException('The limit cannot be less than 1');
            }
            elseif($limit > Configuration::getPoliciesConfiguration()->getEncryptionChannelIncomingLimit())
            {
                throw new InvalidArgumentException(sprintf('The limit cannot exceed a value of %d', Configuration::getPoliciesConfiguration()->getEncryptionChannelIncomingLimit()));
            }

            try
            {
                $offset = ($page - 1) * $limit;
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE receiving_peer_address=:peer_address LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':peer_address', $peerAddress);
                $stmt->bindParam(':limit', $limit);
                $stmt->bindParam(':offset', $offset);
                $stmt->execute();

                $results = $stmt->fetchAll();

                if(!$results)
                {
                    return [];
                }

                return array_map(fn($result) => EncryptionChannelRecord::fromArray($result), $results);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve incoming encryption channels', $e);
            }
        }

        /**
         * Returns an array of outgoing channels for the given peer address
         *
         * @param string|PeerAddress $peerAddress The Peer Address of the caller
         * @param int $page The page number
         * @param int $limit The limit of records to return
         * @return EncryptionChannelRecord[] An array of channel records
         * @throws DatabaseOperationException Thrown if there was a database error while retrieving the records
         */
        public static function getOutgoingChannels(string|PeerAddress $peerAddress, int $page=1, int $limit=100): array
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }
            elseif(!Validator::validatePeerAddress($peerAddress))
            {
                throw new InvalidArgumentException('Invalid Peer Address');
            }

            if($page < 1)
            {
                throw new InvalidArgumentException('The page number cannot be less than 1');
            }

            if($limit < 1)
            {
                throw new InvalidArgumentException('The limit cannot be less than 1');
            }
            elseif($limit > Configuration::getPoliciesConfiguration()->getEncryptionChannelOutgoingLimit())
            {
                throw new InvalidArgumentException(sprintf('The limit cannot exceed a value of %d', Configuration::getPoliciesConfiguration()->getEncryptionChannelOutgoingLimit()));
            }

            try
            {
                $offset = ($page -1) * $limit;
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE calling_peer_address=:peer_address ORDER BY created LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':peer_address', $peerAddress);
                $stmt->bindParam(':limit', $limit);
                $stmt->bindParam(':offset', $offset);
                $stmt->execute();

                $results = $stmt->fetchAll();

                if(!$results)
                {
                    return [];
                }

                return array_map(fn($result) => EncryptionChannelRecord::fromArray($result), $results);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve outgoing encryption channels', $e);
            }
        }

        /**
         * Returns an array of channels that are awaiting the receiver to accept the channel
         *
         * @param string|PeerAddress $peerAddress The Peer Address of the receiver
         * @param int $page The page number
         * @param int $limit The limit of records to return
         * @return EncryptionChannelRecord[] An array of channel records
         * @throws DatabaseOperationException Thrown if there was a database error while retrieving the records
         */
        public static function getChannelRequests(string|PeerAddress $peerAddress, int $page=1, int $limit=100): array
        {
            if(is_string($peerAddress) && !Validator::validatePeerAddress($peerAddress))
            {
                throw new InvalidArgumentException('Invalid Peer Address');
            }

            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }

            if($page < 1)
            {
                throw new InvalidArgumentException('The page number cannot be less than 1');
            }

            if($limit < 1)
            {
                throw new InvalidArgumentException('The limit cannot be less than 1');
            }
            elseif($limit > Configuration::getPoliciesConfiguration()->getEncryptionChannelRequestsLimit())
            {
                throw new InvalidArgumentException(sprintf('The limit cannot exceed a value of %d', Configuration::getPoliciesConfiguration()->getEncryptionChannelRequestsLimit()));
            }


            try
            {
                $offset = ($page -1) * $limit;
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE receiving_peer_address=:peer_address AND status=:status LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':peer_address', $peerAddress);
                $status = EncryptionChannelStatus::AWAITING_RECEIVER->value;
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':limit', $limit);
                $stmt->bindParam(':offset', $offset);
                $stmt->execute();

                $results = $stmt->fetchAll();

                if(!$results)
                {
                    return [];
                }

                return array_map(fn($result) => EncryptionChannelRecord::fromArray($result), $results);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve channel requests', $e);
            }
        }

        /**
         * Submits data into the encryption channel
         *
         * @param string $channelUuid The Unique Universal Identifier of the encryption channel
         * @param EncryptionMessageRecipient $recipient The recipient of the message
         * @param string $checksum The SHA512 checksum of the decrypted data content
         * @param string $data The encrypted data of the message
         * @param string|null $messageUuid Optional. The UUID of the message, used for server-to-server replication
         * @param int|null $messageTimestamp Optional. The Timestamp of the message, used for server-to-server replication
         * @return string Returns the UUID of the message, if $uuid was provided then it's value is returned.
         * @throws DatabaseOperationException Thrown if there was a database error while inserting the record
         */
        public static function sendMessage(string  $channelUuid, EncryptionMessageRecipient $recipient, string $checksum, string $data,
                                           ?string $messageUuid=null, ?int $messageTimestamp=null): string
        {
            if($messageUuid === null)
            {
                $messageUuid = Uuid::v4()->toRfc4122();
            }
            elseif(!Validator::validateUuid($messageUuid))
            {
                throw new InvalidArgumentException('Invalid UUID V4 of the message');
            }

            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('Invalid UUID V4 of the channel');
            }

            if(!Cryptography::validateSha512($checksum))
            {
                throw new InvalidArgumentException('Invalid checksum, must be SHA512');
            }

            if(empty($data))
            {
                throw new InvalidArgumentException('Data cannot be empty');
            }

            if($messageTimestamp === null)
            {
                $messageTimestamp = time();
            }
            elseif(!Validator::isTimestampInRange($messageTimestamp, 3600))
            {
                throw new InvalidArgumentException('Invalid timestamp, must be within 1 hour');
            }

            $currentMessageCount = self::getMessageCount($channelUuid);
            if($currentMessageCount > Configuration::getPoliciesConfiguration()->getEncryptionChannelMaxMessages())
            {
                // Delete the oldest messages to make room for the new one
                self::deleteMessages($channelUuid, self::getOldestMessage($channelUuid, (
                    $currentMessageCount - Configuration::getPoliciesConfiguration()->getEncryptionChannelMaxMessages()
                )));
            }

            try
            {
                $stmt = Database::getConnection()->prepare('INSERT INTO encryption_channels_com (uuid, channel_uuid, recipient, checksum, data, timestamp) VALUES (:uuid, :channel_uuid, :recipient, :checksum, :data, :timestamp)');
                $stmt->bindParam(':uuid', $messageUuid);
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':recipient', $recipient);
                $stmt->bindParam(':checksum', $checksum);
                $stmt->bindParam(':data', $data);
                $stmt->bindParam(':timestamp', $messageTimestamp);

                $stmt->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to send data through the encryption channel', $e);
            }

            return $messageUuid;
        }

        /**
         * Obtains a message record from the database
         *
         * @param string $channelUuid The Unique Universal Identifier for the channel
         * @param string $messageUuid The Unique Universal Identifier for the message
         * @return EncryptionChannelMessageRecord|null Returns the message record if found, null otherwise
         * @throws DatabaseOperationException Thrown if there was a database operation error
         */
        public static function getMessageRecord(string $channelUuid, string $messageUuid): ?EncryptionChannelMessageRecord
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if(!Validator::validateUuid($messageUuid))
            {
                throw new InvalidArgumentException('The given Message UUID is not a valid V4 UUID');
            }

            try
            {
                $stmt = Database::getConnection()->prepare("SELECT * FROM encryption_channels_com WHERE channel_uuid=:channel_uuid AND uuid=:message_uuid LIMIT 1");
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':message_uuid', $messageUuid);
                $stmt->execute();
                $result = $stmt->fetch();

                return $result ? EncryptionChannelMessageRecord::fromArray($result) : null;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to retrieve requested message record %s (Channel UUID: %s) from the database', $channelUuid, $messageUuid), $e);
            }
        }

        /**
         * Returns an array of EncryptionChannelMessageRecord objects sorted by the Timestamp
         *
         * @param string $channelUuid The Unique Universal Identifier of the channel
         * @param EncryptionMessageRecipient|string $recipient The recipient of the receiver
         * @return EncryptionChannelMessageRecord[] An array of message objects returned
         * @throws DatabaseOperationException Thrown if there was a database operation error
         */
        public static function receiveData(string $channelUuid, EncryptionMessageRecipient|string $recipient, int $limit=100): array
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            elseif(is_string($recipient))
            {
                $recipient = EncryptionMessageRecipient::tryFrom($recipient);
                if($recipient === null)
                {
                    throw new InvalidArgumentException('The given recipient is not a valid EncryptionMessageRecipient');
                }
            }

            if($recipient instanceof EncryptionMessageRecipient)
            {
                $recipient = $recipient->value;
            }
            else
            {
                throw new InvalidArgumentException('The given recipient is not a valid EncryptionMessageRecipient');
            }

            if($limit < 1)
            {
                throw new InvalidArgumentException('The limit cannot be less than 1');
            }

            try
            {
                $stmt = Database::getConnection()->prepare("SELECT * FROM encryption_channels_com WHERE channel_uuid=:channel_uuid AND recipient=:recipient AND status='SENT' ORDER BY timestamp LIMIT :limit");
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':recipient', $recipient);
                $stmt->bindParam(':limit', $limit);
                $stmt->execute();
                $results = $stmt->fetchAll();

                if(!$results)
                {
                    return [];
                }

                return array_map(fn($result) => EncryptionChannelMessageRecord::fromArray($result), $results);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while trying to receive new data from the database', $e);
            }
        }

        /**
         * Acknowledges the requested message was received
         *
         * @param string $channelUuid The Unique Universal identifier of the channel
         * @param string $messageUuid The Unique Universal Identifier of the message
         * @return void
         * @throws DatabaseOperationException Thrown if there was an error with the database operation
         */
        public static function acknowledgeMessage(string $channelUuid, string $messageUuid): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if(!Validator::validateUuid($messageUuid))
            {
                throw new InvalidArgumentException('The given Message UUID is not a valid V4 uuid');
            }

            try
            {
                $stmt = Database::getConnection()->prepare("UPDATE encryption_channels_com SET status='RECEIVED' WHERE channel_uuid=:channel_uuid AND uuid=:message_uuid LIMIT 1");
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':message_uuid', $messageUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while acknowledging the message record', $e);
            }
        }

        /**
         * Acknowledges a batch of messages as received
         *
         * @param string $channelUuid The Unique Universal Identifier of the channel
         * @param array $messageUuids An array of message UUIDs to acknowledge
         * @return void
         * @throws DatabaseOperationException Thrown if there was an error with the database operation
         */
        public static function acknowledgeMessagesBatch(string $channelUuid, array $messageUuids): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if(empty($messageUuids))
            {
                throw new InvalidArgumentException('The given Message UUIDs array is empty');
            }

            foreach($messageUuids as $messageUuid)
            {
                if(!Validator::validateUuid($messageUuid))
                {
                    throw new InvalidArgumentException('The given Message UUID is not a valid V4 uuid');
                }
            }

            $placeholders = implode(',', array_fill(0, count($messageUuids), '?'));
            $query = "UPDATE encryption_channels_com SET status='RECEIVED' WHERE channel_uuid=:channel_uuid AND uuid IN ($placeholders)";

            try
            {
                $stmt = Database::getConnection()->prepare($query);
                $stmt->bindParam(':channel_uuid', $channelUuid);
                foreach($messageUuids as $index => $messageUuid)
                {
                    $stmt->bindValue($index + 1, $messageUuid);
                }

                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while acknowledging the message records', $e);
            }
        }

        /**
         * Rejects the requested message
         *
         * @param string $channelUuid The Unique Universal Identifier of the channel
         * @param string $messageUuid The Unique Universal Identifier of the message
         * @param bool $isServer If True, the message will be rejected as "SERVER_REJECTED" otherwise "PEER_REJECTED"
         * @return void
         * @throws DatabaseOperationException Thrown if there was an error with the database operation
         */
        public static function rejectMessage(string $channelUuid, string $messageUuid, bool $isServer=false): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if(!Validator::validateUuid($messageUuid))
            {
                throw new InvalidArgumentException('The given Message UUId is not a valid V4 uuid');
            }

            try
            {
                $status = $isServer ? EncryptionChannelStatus::SERVER_REJECTED->value : EncryptionChannelStatus::PEER_REJECTED->value;
                $stmt = Database::getConnection()->prepare("UPDATE encryption_channels_com SET status=:status WHERE channel_uuid=:channel_uuid AND uuid=:message_uuid LIMIT 1");
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':message_uuid', $messageUuid);

                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while rejecting the message record', $e);
            }
        }

        /**
         * Returns the total message count in a given channel
         *
         * @param string $channelUuid The channel UUID to check
         * @return int The number of messages there is
         * @throws DatabaseOperationException Thrown if there was a database operation error
         */
        public static function getMessageCount(string $channelUuid): int
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            try
            {
                $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM encryption_channels_com WHERE channel_uuid=:channel_uuid");
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->execute();

                return (int)$stmt->fetchColumn();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while trying to retrieve the message count', $e);
            }
        }

        /**
         * Returns an array of oldest messages in the queue, sorted by timestamp
         *
         * @param string $channelUuid The channel UUID to check
         * @param int $amount The number of messages to retrieve
         * @return string[] An array of message records
         * @throws DatabaseOperationException Thrown if there was a database operation error
         */
        public static function getOldestMessage(string $channelUuid, int $amount=1): array
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if($amount < 1)
            {
                throw new InvalidArgumentException('The amount of messages to retrieve cannot be less than 1');
            }

            try
            {
                $stmt = Database::getConnection()->prepare("SELECT uuid FROM encryption_channels_com WHERE channel_uuid=:channel_uuid AND status='SENT' ORDER BY timestamp LIMIT :amount");
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':amount', $amount, PDO::PARAM_INT);
                $stmt->execute();

                $results = $stmt->fetchAll();
                if(!$results)
                {
                    return [];
                }

                return array_map(fn($result) => $result['uuid'], $results);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while trying to retrieve the oldest message', $e);
            }
        }

        /**
         * Deletes a message record from the database
         *
         * @param string $channelUuid The Unique Universal Identifier of the channel
         * @param string $messageUuid The Unique Universal Identifier of the message
         * @return void
         * @throws DatabaseOperationException Thrown if there was an error with the database operation
         */
        public static function deleteMessage(string $channelUuid, string $messageUuid): void
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if(!Validator::validateUuid($messageUuid))
            {
                throw new InvalidArgumentException('The given Message UUID is not a valid V4 UUID');
            }

            try
            {
                $stmt = Database::getConnection()->prepare("DELETE FROM encryption_channels_com WHERE channel_uuid=:channel_uuid AND uuid=:message_uuid LIMIT 1");
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':message_uuid', $messageUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while trying to delete the message record', $e);
            }
        }

        /**
         * Deletes a batch of messages from the database
         *
         * @param string $channelUuid The Unique Universal Identifier of the channel
         * @param array $messageUuids An array of message UUIDs to delete
         * @return void
         * @throws DatabaseOperationException Thrown if there was an error with the database operation
         */
        public static function deleteMessages(string $channelUuid, array $messageUuids): void
        {
            if(count($messageUuids) === 0)
            {
                return;
            }
            elseif(count($messageUuids) === 1)
            {
                self::deleteMessage($channelUuid, $messageUuids[0]);
                return;
            }

            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if(empty($messageUuids))
            {
                throw new InvalidArgumentException('The given Message UUIDs array is empty');
            }

            $placeholders = implode(',', array_fill(0, count($messageUuids), '?'));
            $query = "DELETE FROM encryption_channels_com WHERE channel_uuid=:channel_uuid AND uuid IN ($placeholders)";

            try
            {
                $stmt = Database::getConnection()->prepare($query);
                $stmt->bindParam(':channel_uuid', $channelUuid);
                foreach($messageUuids as $index => $messageUuid)
                {
                    $stmt->bindValue($index + 1, $messageUuid);
                }

                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while deleting the message records', $e);
            }
        }
    }