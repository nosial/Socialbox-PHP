<?php

    namespace Socialbox\Managers;

    use InvalidArgumentException;
    use ncc\ThirdParty\Symfony\Uid\UuidV4;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Enums\Status\EncryptionChannelState;
    use Socialbox\Enums\Types\CommunicationRecipientType;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\ChannelMessageRecord;
    use Socialbox\Objects\Database\EncryptionChannelRecord;
    use Socialbox\Objects\PeerAddress;

    class EncryptionChannelManager
    {
        /**
         * Creates a new encryption channel between two peers.
         *
         * @param PeerAddress|string $callingPeer The peer that is creating the channel.
         * @param PeerAddress|string $receivingPeer The peer that is receiving the channel.
         * @param string $signatureUuid The UUID of the signature used to create the channel.
         * @param string $signingPublicKey The public key used for signing.
         * @param string $encryptionPublicKey The public key used for encryption.
         * @param string $transportEncryptionAlgorithm The algorithm used for transport encryption.
         * @return string The UUID of the created channel.
         * @throws DatabaseOperationException If an error occurs while creating the channel.
         */
        public static function createChannel(PeerAddress|string $callingPeer, PeerAddress|string $receivingPeer,
            string $signatureUuid, string $signingPublicKey, string $encryptionPublicKey, string $transportEncryptionAlgorithm,
            ?string $uuid=null
        ): string
        {
            if(is_string($callingPeer))
            {
                $callingPeer = PeerAddress::fromAddress($callingPeer);
            }

            if(is_string($receivingPeer))
            {
                $receivingPeer = PeerAddress::fromAddress($receivingPeer);
            }

            if(!Cryptography::validatePublicSigningKey($signingPublicKey))
            {
                throw new InvalidArgumentException('Invalid signing public key provided');
            }

            if(!Cryptography::validatePublicEncryptionKey($encryptionPublicKey))
            {
                throw new InvalidArgumentException('Invalid encryption public key provided');
            }

            $transportEncryptionAlgorithm = strtolower($transportEncryptionAlgorithm);
            if(!Cryptography::isSupportedAlgorithm($transportEncryptionAlgorithm))
            {
                throw new InvalidArgumentException('Unsupported transport encryption algorithm');
            }

            if($uuid === null)
            {
                $uuid = UuidV4::v4()->toRfc4122();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('INSERT INTO encryption_channels (uuid, calling_peer, calling_signature_uuid, calling_signature_public_key, calling_encryption_public_key, receiving_peer, transport_encryption_algorithm) VALUES (:uuid, :calling_peer, :calling_signature_uuid, :calling_signature_public_key, :calling_encryption_public_key, :receiving_peer, :transport_encryption_algorithm)');
                $stmt->bindParam(':uuid', $uuid);
                $callingPeerAddress = $callingPeer->getAddress();
                $stmt->bindParam(':calling_peer', $callingPeerAddress);
                $stmt->bindParam(':calling_signature_uuid', $signatureUuid);
                $stmt->bindParam(':calling_signature_public_key', $signingPublicKey);
                $stmt->bindParam(':calling_encryption_public_key', $encryptionPublicKey);
                $receivingPeerAddress = $receivingPeer->getAddress();
                $stmt->bindParam(':receiving_peer', $receivingPeerAddress);
                $stmt->bindParam(':transport_encryption_algorithm', $transportEncryptionAlgorithm);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to create the encryption channel', $e);
            }

            return $uuid;
        }

        /**
         * Retrieves the incoming encryption channels for the specified peer.
         *
         * @param string|PeerAddress $peerAddress The peer to retrieve the channels for.
         * @param int $limit The maximum number of channels to retrieve.
         * @param int $page The page of channels to retrieve.
         * @return EncryptionChannelRecord[] The incoming channels for the peer.
         * @throws DatabaseOperationException If an error occurs while retrieving the channels.
         * @throws \DateMalformedStringException If the created date is not a valid date string.
         */
        public static function getChannels(string|PeerAddress $peerAddress, int $limit=100, int $page=0): array
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE calling_peer=:address OR receiving_peer=:address LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':address', $peerAddress);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $offset = $page * $limit;
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll();

                $channels = [];
                foreach($results as $result)
                {
                    $channels[] = new EncryptionChannelRecord($result);
                }

                return $channels;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the encryption channels', $e);
            }
        }

        /**
         * Retrieves the incoming encryption channels for the specified peer.
         *
         * @param string|PeerAddress $peerAddress The peer to retrieve the channels for.
         * @param int $limit The maximum number of channels to retrieve.
         * @param int $page The page of channels to retrieve.
         * @return EncryptionChannelRecord[] The incoming channels for the peer.
         * @throws DatabaseOperationException If an error occurs while retrieving the channels.
         * @throws \DateMalformedStringException If the created date is not a valid date string.
         */
        public static function getRequests(string|PeerAddress $peerAddress, int $limit=100, int $page=0): array
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE receiving_peer=:address AND state=:state LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':address', $peerAddress);
                $state = EncryptionChannelState::AWAITING_RECEIVER->value;
                $stmt->bindParam(':state', $state);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $offset = $page * $limit;
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll();

                $channels = [];
                foreach($results as $result)
                {
                    $channels[] = new EncryptionChannelRecord($result);
                }

                return $channels;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the encryption channels', $e);
            }
        }

        /**
         * Retrieves the incoming encryption channels for the specified peer.
         *
         * @param string|PeerAddress $peerAddress The peer to retrieve the channels for.
         * @param int $limit The maximum number of channels to retrieve.
         * @param int $page The page of channels to retrieve.
         * @return EncryptionChannelRecord[] The incoming channels for the peer.
         * @throws DatabaseOperationException If an error occurs while retrieving the channels.
         * @throws \DateMalformedStringException If the created date is not a valid date string.
         */
        public static function getIncomingChannels(string|PeerAddress $peerAddress, int $limit=100, int $page=0): array
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerUuid = $peerAddress->getAddress();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE receiving_peer=:address LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':address', $peerUuid);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $offset = $page * $limit;
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll();

                $channels = [];
                foreach($results as $result)
                {
                    $channels[] = new EncryptionChannelRecord($result);
                }

                return $channels;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the encryption channels', $e);
            }
        }

        /**
         * Retrieves the outgoing channels for the specified peer.
         *
         * @param string|PeerAddress $peerAddress The peer to retrieve the channels for.
         * @param int $limit The maximum number of channels to retrieve.
         * @param int $page The page of channels to retrieve.
         * @return EncryptionChannelRecord[] The outgoing channels for the specified peer.
         * @throws DatabaseOperationException If an error occurs while retrieving the channels.
         * @throws \DateMalformedStringException If the created date is not a valid date string.
         */
        public static function getOutgoingChannels(string|PeerAddress $peerAddress, int $limit=100, int $page=0): array
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE calling_peer=:address LIMIT :limit OFFSET :offset');
                $stmt->bindParam(':address', $peerAddress);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $offset = $page * $limit;
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll();

                $channels = [];
                foreach($results as $result)
                {
                    $channels[] = new EncryptionChannelRecord($result);
                }

                return $channels;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the encryption channels', $e);
            }
        }

        /**
         * Declines the encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel to decline.
         * @throws DatabaseOperationException If an error occurs while declining the channel.
         */
        public static function declineChannel(string $channelUuid): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE encryption_channels SET state=:state WHERE uuid=:uuid');
                $state = EncryptionChannelState::DECLINED->value;
                $stmt->bindParam(':state', $state);
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to decline the encryption channel', $e);
            }
        }

        /**
         * Accepts the encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel to accept.
         * @param string $signatureUuid The UUID of the signature used to create the channel.
         * @param string $signaturePublicKey The public key used for signing.
         * @param string $encryptionPublicKey The public key used for encryption.
         * @param string $transportEncryptionAlgorithm The algorithm used for transport encryption.
         * @param string $encryptedTransportEncryptionKey The encrypted transport encryption key.
         * @throws DatabaseOperationException If an error occurs while accepting the channel.
         */
        public static function acceptChannel(string $channelUuid, string $signatureUuid, string $signaturePublicKey, string $encryptionPublicKey, string $transportEncryptionAlgorithm, string $encryptedTransportEncryptionKey): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE encryption_channels SET state=:state, receiving_signature_uuid=:receiving_signature_uuid, receiving_signature_public_key=:receiving_signature_public_key, receiving_encryption_public_key=:receiving_encryption_public_key, transport_encryption_algorithm=:transport_encryption_algorithm, transport_encryption_key=:transport_encryption_key WHERE uuid=:uuid');
                $state = EncryptionChannelState::OPENED->value;
                $stmt->bindParam(':state', $state);
                $stmt->bindParam(':receiving_signature_uuid', $signatureUuid);
                $stmt->bindParam(':receiving_signature_public_key', $signaturePublicKey);
                $stmt->bindParam(':receiving_encryption_public_key', $encryptionPublicKey);
                $stmt->bindParam(':transport_encryption_algorithm', $transportEncryptionAlgorithm);
                $stmt->bindParam(':transport_encryption_key', $encryptedTransportEncryptionKey);
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to accept the encryption channel', $e);
            }
        }

        /**
         * Retrieves the encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel to retrieve.
         * @return EncryptionChannelRecord|null The record of the encryption channel. Null if the channel does not exist.
         * @throws DatabaseOperationException If an error occurs while retrieving the channel.
         * @throws \DateMalformedStringException If the created date is not a valid date string.
         */
        public static function getChannel(string $channelUuid): ?EncryptionChannelRecord
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_channels WHERE uuid=:uuid');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();
                $result = $stmt->fetch();

                if($result === false)
                {
                    return null;
                }

                return new EncryptionChannelRecord($result);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the encryption channel', $e);
            }
        }

        /**
         * Deletes the encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel to delete.
         * @return void
         *@throws DatabaseOperationException If an error occurs while deleting the channel.
         */
        public static function deleteChannel(string $channelUuid): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare('DELETE FROM encryption_channels WHERE uuid=:uuid');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete the encryption channel', $e);
            }
        }

        /**
         * Updates the state of the encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel to update.
         * @return EncryptionChannelState The current state of the channel.
         * @throws DatabaseOperationException If an error occurs while updating the channel state.
         */
        public static function getChannelState(string $channelUuid): EncryptionChannelState
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT state FROM encryption_channels WHERE uuid=:uuid');
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();

                return EncryptionChannelState::from($stmt->fetchColumn());
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the encryption channel state', $e);
            }
        }

        /**
         * Updates the state of the encryption channel with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel to update.
         * @param EncryptionChannelState $state The new state of the channel.
         * @return void The current state of the channel.
         * @throws DatabaseOperationException If an error occurs while updating the channel state.
         */
        public static function updateChannelState(string $channelUuid, EncryptionChannelState $state): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE encryption_channels SET state=:state WHERE uuid=:uuid');
                $state = $state->value;
                $stmt->bindParam(':state', $state);
                $stmt->bindParam(':uuid', $channelUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the encryption channel state', $e);
            }
        }

        /**
         * Checks if a channel with the provided UUID exists.
         *
         * @param string $uuid The UUID of the channel to check.
         * @return bool True if the channel exists, False otherwise.
         * @throws DatabaseOperationException If an error occurs while checking the channel.
         */
        public static function channelExists(string $uuid): bool
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM encryption_channels WHERE uuid=:uuid');
                $stmt->bindParam(':uuid', $uuid);
                $stmt->execute();

                return $stmt->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('There was an error while trying to check if the channel UUID exists', $e);
            }
        }

        /**
         * Sends data to the specified channel.
         *
         * @param string $channelUuid The UUID of the channel to send the data to.
         * @param string $message The message to send.
         * @param string $signature The signature of the message.
         * @param CommunicationRecipientType $recipient The recipient type.
         * @return string The UUID of the sent message.
         * @throws DatabaseOperationException If an error occurs while sending the message.
         */
        public static function sendData(string $channelUuid, string $message, string $signature, CommunicationRecipientType $recipient): string
        {
            $uuid = UuidV4::v4()->toRfc4122();

            try
            {
                $stmt = Database::getConnection()->prepare('INSERT INTO channel_com (uuid, channel_uuid, recipient, message, signature) VALUES (:uuid, :channel_uuid, :recipient, :message, :signature)');
                $stmt->bindParam(':uuid', $uuid);
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $recipient = $recipient->value;
                $stmt->bindParam(':recipient', $recipient);
                $stmt->bindParam(':message', $message);
                $stmt->bindParam(':signature', $signature);

                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to send the message', $e);
            }

            return $uuid;
        }

        /**
         * Retrieves the messages for the specified channel and recipient.
         *
         * @param string $channelUuid The UUID of the channel to retrieve the messages for.
         * @param CommunicationRecipientType $recipient The recipient type to retrieve the messages for.
         * @return ChannelMessageRecord[] The messages for the specified channel and recipient.
         * @throws DatabaseOperationException If an error occurs while retrieving the messages.
         */
        public static function receiveData(string $channelUuid, CommunicationRecipientType $recipient): array
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM channel_com WHERE channel_uuid=:channel_uuid AND recipient=:recipient AND received=0 ORDER BY timestamp');
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $recipient = $recipient->value;
                $stmt->bindParam(':recipient', $recipient);
                $stmt->execute();
                $results = $stmt->fetchAll();

                $messages = [];
                foreach($results as $result)
                {
                    $messages[] = new ChannelMessageRecord($result);
                }

                return $messages;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the messages', $e);
            }
        }

        /**
         * Retrieves the message with the specified UUID.
         *
         * @param string $channelUuid The UUID of the channel to retrieve the message for.
         * @param string $messageUuid The UUID of the message to retrieve.
         * @return ChannelMessageRecord|null The message with the specified UUID. Null if the message does not exist.
         * @throws DatabaseOperationException If an error occurs while retrieving the message.
         */
        public static function getData(string $channelUuid, string $messageUuid): ?ChannelMessageRecord
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM channel_com WHERE channel_uuid=:channel_uuid AND uuid=:uuid');
                $stmt->bindParam(':channel_uuid', $channelUuid);
                $stmt->bindParam(':uuid', $messageUuid);
                $stmt->execute();
                $result = $stmt->fetch();

                if($result === false)
                {
                    return null;
                }

                return new ChannelMessageRecord($result);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the message', $e);
            }
        }

        /**
         * Marks the message with the specified UUID as received.
         *
         * @param string $uuid The UUID of the message to mark as received.
         * @throws DatabaseOperationException If an error occurs while marking the message as received.
         */
        public static function markDataAsReceived(string $uuid): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE channel_com SET received=1 WHERE uuid=:uuid');
                $stmt->bindParam(':uuid', $uuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to mark the message as received', $e);
            }
        }

        /**
         * Deletes the message with the specified UUID.
         *
         * @param string $uuid The UUID of the message to delete.
         * @throws DatabaseOperationException If an error occurs while deleting the message.
         */
        public static function deleteData(string $uuid): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare('DELETE FROM channel_com WHERE uuid=:uuid');
                $stmt->bindParam(':uuid', $uuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete the message', $e);
            }
        }
    }