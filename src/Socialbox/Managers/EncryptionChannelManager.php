<?php


    namespace Socialbox\Managers;


    use InvalidArgumentException;
    use PDOException;
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
         * @param string|null $channelUUid The UUID of the channel. If not provided, a new UUID will be generated.
         * @return string The UUID of the created channel.
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function createChannel(string|PeerAddress $callingPeer, string|PeerAddress $receivingPeer,
            string                                              $callingPublicEncryptionKey, ?string $channelUUid=null): string
        {
            if($channelUUid === null)
            {
                $channelUUid = Uuid::v4()->toRfc4122();
            }
            elseif(!Validator::validateUuid($channelUUid))
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
                $channelUUid = $channelUUid ?? Uuid::v4()->toRfc4122();
                $stmt = Database::getConnection()->prepare('INSERT INTO encryption_channels (uuid, calling_peer_address, receiving_peer_address, calling_peer_address, calling_public_encryption_key) VALUES (:uuid, :calling_peer_address, :receiving_peer_address, :calling_peer_address, :calling_public_encryption_key)');
                $stmt->bindParam(':uuid', $channelUUid);
                $stmt->bindParam(':calling_peer_address', $callingPeer);
                $stmt->bindParam(':receiving_peer_address', $receivingPeer);
                $stmt->bindParam(':calling_public_encryption_key', $callingPublicEncryptionKey);
                $stmt->execute();

                return $channelUUid;
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
         * @param string $peerAddress The Peer Address of the caller
         * @return EncryptionChannelRecord[] An array of channel records
         * @throws DatabaseOperationException Thrown if there was a database error while retrieving the records
         */
        public static function getIncomingChannels(string $peerAddress): array
        {
            if(!Validator::validatePeerAddress($peerAddress))
            {
                throw new InvalidArgumentException('Invalid Peer Address');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE receiving_peer_address=:peer_address');
                $stmt->bindParam(':peer_address', $peerAddress);
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
         * @param string $peerAddress The Peer Address of the caller
         * @return EncryptionChannelRecord[] An array of channel records
         * @throws DatabaseOperationException Thrown if there was a database error while retrieving the records
         */
        public static function getOutgoingChannels(string $peerAddress): array
        {
            if(!Validator::validatePeerAddress($peerAddress))
            {
                throw new InvalidArgumentException('Invalid Peer Address');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE calling_peer_address=:peer_address');
                $stmt->bindParam(':peer_address', $peerAddress);
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
         * @param string $peerAddress The Peer Address of the receiver
         * @return EncryptionChannelRecord[] An array of channel records
         * @throws DatabaseOperationException Thrown if there was a database error while retrieving the records
         */
        public static function getChannelRequests(string $peerAddress): array
        {
            if(!Validator::validatePeerAddress($peerAddress))
            {
                throw new InvalidArgumentException('Invalid Peer Address');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE receiving_peer_address=:peer_address AND status=:status');
                $stmt->bindParam(':peer_address', $peerAddress);
                $status = EncryptionChannelStatus::AWAITING_RECEIVER->value;
                $stmt->bindParam(':status', $status);
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

            if($messageTimestamp === null)
            {
                $messageTimestamp = time();
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
        public static function receiveData(string $channelUuid, EncryptionMessageRecipient|string $recipient): array
        {
            if(!Validator::validateUuid($channelUuid))
            {
                throw new InvalidArgumentException('The given Channel UUID is not a valid V4 UUID');
            }

            if($recipient instanceof EncryptionMessageRecipient)
            {
                $recipient = $recipient->value;
            }

            try
            {
                $stmt = Database::getConnection()->prepare("SELECT * FROM encryption_channels_com WHERE channel_uuid=:channel_uuid AND recipient=:recipient AND status='SENT' ORDER BY timestamp LIMIT 100");
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':recipient', $recipient);
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
    }