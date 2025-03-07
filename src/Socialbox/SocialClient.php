<?php

    /** @noinspection PhpUnused */

    namespace Socialbox;

    use InvalidArgumentException;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\RpcClient;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Enums\Status\SignatureVerificationStatus;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\Client\EncryptionChannelInstance;
    use Socialbox\Objects\Client\EncryptionChannelSecret;
    use Socialbox\Objects\Client\ExportedSession;
    use Socialbox\Objects\Client\SignatureKeyPair;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\Contact;
    use Socialbox\Objects\Standard\EncryptionChannel;
    use Socialbox\Objects\Standard\ImageCaptchaVerification;
    use Socialbox\Objects\Standard\InformationFieldState;
    use Socialbox\Objects\Standard\Peer;
    use Socialbox\Objects\Standard\ServerDocument;
    use Socialbox\Objects\Standard\SessionState;
    use Socialbox\Objects\Standard\Signature;

    class SocialClient extends RpcClient
    {
        /**
         * Constructs the object from an array of data.
         *
         * @param string|PeerAddress $identifiedAs The address of the peer to connect to.
         * @param string|null $server Optional. The domain of the server to connect to if different from the identified
         * @param ExportedSession|null $exportedSession Optional. The exported session to use for communication.
         * @throws CryptographyException If the public key is invalid.
         * @throws DatabaseOperationException If the database operation fails.
         * @throws ResolutionException If the domain cannot be resolved.
         * @throws RpcException If the RPC request fails.
         */
        public function __construct(PeerAddress|string $identifiedAs, ?string $server=null, ?ExportedSession $exportedSession=null)
        {
            parent::__construct($identifiedAs, $server, $exportedSession);
        }

        /**
         * Create a new signing keypair, sends it to the server, and saves it locally with the session so that the client
         * can use the SigningKey pair for signing and verifying signatures in the future.
         *
         * This is not a server-side operation, the server only stores the public key and associates it with the peer's
         * profile. The private key is stored locally and is never sent to the server.
         *
         * @param string|null $name Optional. The name of the signature
         * @param int|null $expires Optional. The Unix timestamp of the expiration time
         * @return string The UUID of the signature
         * @throws CryptographyException Thrown if there was an error while generating the signing key pair
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function newSignature(?string $name=null, ?int $expires=null): string
        {
            $signature = Cryptography::generateSigningKeyPair();
            $uuid = $this->settingsAddSignature($signature->getPublicKey(), $name, $expires);

            $this->addSigningKey(new SignatureKeyPair([
                'uuid' => $uuid,
                'name' => $name,
                'public_key' => $signature->getPublicKey(),
                'private_key' => $signature->getPrivateKey(),
                'expires' => $expires
            ]));

            return $uuid;
        }

        /**
         * Creates a new encryption channel with the given peer, generates a new encryption key pair, and sends the public
         * key to the receiving peer. The private key is stored locally and is never sent to the server.
         *
         * @param PeerAddress|string $receivingPeer The address of the peer to create the channel with
         * @return string The UUID of the encryption channel
         * @throws CryptographyException Thrown if there was an error while generating the encryption key pair
         */
        public function newEncryptionChannel(string|PeerAddress $receivingPeer): string
        {
            if($receivingPeer instanceof PeerAddress)
            {
                $receivingPeer = $receivingPeer->getAddress();
            }

            $encryptionKeyPair = Cryptography::generateEncryptionKeyPair();
            $encryptionChannelUuid = $this->encryptionCreateChannel($receivingPeer, $encryptionKeyPair->getPublicKey());
            $this->addEncryptionChannelSecret(new EncryptionChannelSecret([
                'channel_uuid' => $encryptionChannelUuid,
                'receiver' => $receivingPeer,
                'local_public_encryption_key' => $encryptionKeyPair->getPublicKey(),
                'local_private_encryption_key' => $encryptionKeyPair->getPrivateKey()
            ]));

            return $encryptionChannelUuid;
        }

        /**
         * Waits for the encryption channel to be accepted by the receiving peer, returns True if the channel was accepted
         * or False if the channel was not accepted within the timeout period.
         *
         * @param string $channelUuid The UUID of the encryption channel to wait for
         * @param int|null $timeout Optional. The timeout period in seconds
         * @return bool Returns True if the channel was accepted, False if the channel was not accepted within the timeout period
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function waitForEncryptionChannel(string $channelUuid, ?int $timeout=30): bool
        {
            if($this->getEncryptionChannelSecret($channelUuid) === null)
            {
                throw new InvalidArgumentException('Encryption Channel was not created with newEncryptionChannel() or defined with addEncryptionChannelSecret()');
            }

            $start = time();
            while(true)
            {
                if($timeout !== null && time() - $start > $timeout)
                {
                    break;
                }

                $encryptionChannel = $this->encryptionGetChannel($channelUuid);
                if($encryptionChannel->getReceivingPublicEncryptionKey() !== null)
                {
                    $this->getEncryptionChannelSecret($channelUuid)->setReceivingPublicEncryptionKey($encryptionChannel->getReceivingPublicEncryptionKey());
                    return true;
                }

                sleep(1);
            }

            return false;
        }

        /**
         * Accepts an encryption channel with the given unique universal identifier, generates a new encryption key pair,
         * and sends the public key to the calling peer. The private key is stored locally and is never sent to the server.
         *
         * @param string $channelUuid The UUID of the encryption channel to accept
         * @return bool Returns True if the channel was accepted
         * @throws CryptographyException Thrown if there was an error while generating the encryption key pair
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function acceptEncryptionChannel(string $channelUuid): bool
        {
            $encryptionChannel = $this->encryptionGetChannel($channelUuid);
            $encryptionKeyPair = Cryptography::generateEncryptionKeyPair();
            $this->encryptionAcceptChannel($channelUuid, $encryptionKeyPair->getPublicKey());

            $this->addEncryptionChannelSecret(new EncryptionChannelSecret([
                'channel_uuid' => $channelUuid,
                'receiver' => $encryptionChannel->getCallingPeer(),
                'local_public_encryption_key' => $encryptionKeyPair->getPublicKey(),
                'local_private_encryption_key' => $encryptionKeyPair->getPrivateKey(),
                'receiving_public_encryption_key' => $encryptionChannel->getCallingPublicEncryptionKey()
            ]));

            return true;
        }

        /**
         * Creates a new EncryptionChannelInstance object for the given channel UUID.
         *
         * @param string $channelUuid The UUID of the encryption channel
         * @return EncryptionChannelInstance The EncryptionChannelInstance object
         * @throws InvalidArgumentException Thrown if the encryption channel secret does not exist
         */
        public function createEncryptionChannelInstance(string $channelUuid): EncryptionChannelInstance
        {
            if($this->getEncryptionChannelSecret($channelUuid) === null)
            {
                throw new InvalidArgumentException('Encryption Channel was not created with newEncryptionChannel() or defined with addEncryptionChannelSecret()');
            }

            $encryptionChannelSecret = $this->getEncryptionChannelSecret($channelUuid);
            return new EncryptionChannelInstance($this, $encryptionChannelSecret);
        }

        /**
         * Adds a new peer to the AddressBook, returns True upon success or False if the contact already exists in
         * the address book.
         *
         * @param PeerAddress|string $peer The address of the peer to add as a contact
         * @param string|ContactRelationshipType|null $relationship Optional. The relationship for the peer
         * @return bool Returns True if the contact was created, False if it already exists
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookAddContact(PeerAddress|string $peer, null|string|ContactRelationshipType $relationship=ContactRelationshipType::MUTUAL): bool
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            if($relationship instanceof ContactRelationshipType)
            {
                $relationship = $relationship->value;
            }

            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::ADDRESS_BOOK_ADD_CONTACT, parameters: [
                    'peer' => $peer,
                    'relationship' => $relationship?->value
                ])
            )->getResponse()->getResult();
        }

        /**
         * Checks if the given Peer Address exists as a contact in the address book, returns True if it exists or
         * False otherwise.
         *
         * @param PeerAddress|string $peer The address of the peer to check
         * @return bool Returns True if the contact exists, False otherwise
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookContactExists(PeerAddress|string $peer): bool
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::ADDRESS_BOOK_CONTACT_EXISTS, parameters: [
                    'peer' => $peer
                ])
            )->getResponse()->getResult();
        }

        /**
         * Deletes a contact from the address book, returns True if the contact was deleted, or False if the contact
         * does not exist.
         *
         * @param PeerAddress|string $peer The address of the peer to delete
         * @return bool Returns True if the contact was deleted, False if the contact does not exist
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookDeleteContact(PeerAddress|string $peer): bool
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::ADDRESS_BOOK_DELETE_CONTACT, parameters: [
                    'peer' => $peer
                ])
            )->getResponse()->getResult();
        }

        /**
         * Retrieves a contact from the address book, returns the contact as a Contact object.
         *
         * @param PeerAddress|string $peer The address of the peer to retrieve
         * @return Contact The contact as a Contact object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookGetContact(PeerAddress|string $peer): Contact
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            return new Contact($this->sendRequest(
                new RpcRequest(StandardMethods::ADDRESS_BOOK_GET_CONTACT, parameters: [
                    'peer' => $peer
                ])
            )->getResponse()->getResult());
        }

        /**
         * Retrieves a list of contacts from the address book, returns an array of Contact objects.
         *
         * @param int $page Optional. The page number to retrieve
         * @param int|null $limit Optional. The number of contacts to retrieve
         * @return Contact[] An array of Contact objects
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookGetContacts(int $page=0, ?int $limit=null): array
        {
            $request = new RpcRequest(StandardMethods::ADDRESS_BOOK_GET_CONTACTS, parameters: [
                'page' => $page,
                'limit' => $limit
            ]);

            return array_map(fn($contact) => new Contact($contact), $this->sendRequest($request)->getResponse()->getResult());
        }

        /**
         * Revokes a known signature associated with a peer in the address book, returns True if the signature was
         * revoked or False if the signature does not exist.
         *
         * @param PeerAddress|string $peer The address of the peer to revoke the signature from
         * @param string $signatureUuid The UUID of the signature to revoke
         * @return bool Returns True if the signature was revoked, False if the signature does not exist
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookRevokeSignature(PeerAddress|string $peer, string $signatureUuid): bool
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ADDRESS_BOOK_REVOKE_SIGNATURE, parameters: [
                    'peer' => $peer,
                    'signature_uuid' => $signatureUuid
                ])
            )->getResponse()->getResult();
        }

        /**
         * Trusts a known signature associated with a peer in the address book, returns True if the signature was trusted
         * or False if the signature does not exist.
         *
         * @param PeerAddress|string $peer The address of the peer to trust the signature from
         * @param string $signatureUuid The UUID of the signature to trust
         * @return bool Returns True if the signature was trusted, False if the signature does not exist
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookTrustSignature(PeerAddress|string $peer, string $signatureUuid): bool
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ADDRESS_BOOK_TRUST_SIGNATURE, parameters: [
                    'peer' => $peer,
                    'signature_uuid' => $signatureUuid
                ])
            )->getResponse()->getResult();
        }

        /**
         * Updates the relationship of a peer in the address book, returns True if the relationship was updated or False
         * if the relationship does not exist.
         *
         * @param PeerAddress|string $peer The address of the peer to update the relationship for
         * @param ContactRelationshipType|string $relationship The relationship to update to
         * @return bool Returns True if the relationship was updated, False if the relationship does not exist
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function addressBookUpdateRelationship(PeerAddress|string $peer, ContactRelationshipType|string $relationship): bool
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            if($relationship instanceof ContactRelationshipType)
            {
                $relationship = $relationship->value;
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ADDRESS_BOOK_UPDATE_RELATIONSHIP, parameters: [
                    'peer' => $peer,
                    'relationship' => $relationship
                ])
            )->getResponse()->getResult();
        }

        /**
         * Retrieves a list of all available methods that can be called on the server, returns an array of method names.
         *
         * @return string[] An array of method names
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function getAllowedMethods(): array
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::GET_ALLOWED_METHODS)
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the authenticated peer associated with the session, returns the peer as a Peer object.
         *
         * @return Peer The peer as a Peer object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function getSelf(): Peer
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::GET_SELF)
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the session state from the server, returns the session state as a SessionState object.
         *
         * @return SessionState The session state as a SessionState object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function getSessionState(): SessionState
        {
            return new SessionState($this->sendRequest(
                new RpcRequest(StandardMethods::GET_SESSION_STATE)
            )->getResponse()->getResult());
        }

        /**
         * Pings the server to check if it is online, returns True if the server is online.
         *
         * @return true Returns True if the server is online
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function ping(): true
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::PING)
            )->getResponse()->getResult();
        }

        /**
         * Resolves a peer address to a Peer object, returns the peer as a Peer object. This is a decentralized
         * method, meaning that the peer address can be resolved from any address even if the address doesn't
         * belong to the server the request is being sent to.
         *
         * @param PeerAddress|string $peer The address of the peer to resolve
         * @param PeerAddress|string|null $identifiedAs Optional. The address of the peer to identify as
         * @return Peer The peer as a Peer object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function resolvePeer(PeerAddress|string $peer, null|PeerAddress|string $identifiedAs=null): Peer
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return new Peer($this->sendRequest(
                new RpcRequest(StandardMethods::RESOLVE_PEER, parameters: [
                    'peer' => $peer
                ]), true, $identifiedAs
            )->getResponse()->getResult());
        }

        /**
         * Resolves a peer signature to a Signature object, returns the signature as a Signature object. This is
         * a decentralized method, meaning that the signature can be resolved from any address even if the address
         * doesn't belong to the server the request is being sent to.
         *
         * @param PeerAddress|string $peer The address of the peer to resolve the signature from
         * @param string $signatureUuid The UUID of the signature to resolve
         * @return Signature|null The signature as a Signature object, or null if the signature does not exist
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function resolveSignature(PeerAddress|string $peer, string $signatureUuid): ?Signature
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            $result = $this->sendRequest(
                new RpcRequest(StandardMethods::RESOLVE_SIGNATURE, parameters: [
                    'peer' => $peer,
                    'signature_uuid' => $signatureUuid
                ])
            )->getResponse()->getResult();

            if($result === null)
            {
                return null;
            }

            return new Signature($result);
        }

        /**
         * Verifies signature authenticity by resolving the signature UUID and comparing the given parameters with the
         * signature data, returns True if the signature is verified. This is a decentralized method, meaning that any
         * signature UUID can be verified for as long as the $peer parameter is the address of the peer that created the
         * signature.
         *
         * @param PeerAddress|string $peer The address of the peer to verify the signature for
         * @param string $signatureUuid The UUID of the signature to verify
         * @param string $signaturePublicKey The public key that was used to create the signature
         * @param string $signature The signature to verify
         * @param string $sha512 The SHA512 hash of the data that was signed
         * @param int|null $signatureTime Optional. The timestamp of the signature creation time
         * @return SignatureVerificationStatus the status of the verification
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function verifySignature(PeerAddress|string $peer, string $signatureUuid, string $signaturePublicKey, string $signature, string $sha512, ?int $signatureTime=null): SignatureVerificationStatus
        {
            if($peer instanceof PeerAddress)
            {
                $peer = $peer->getAddress();
            }

            return SignatureVerificationStatus::tryFrom($this->sendRequest(
                new RpcRequest(StandardMethods::VERIFY_SIGNATURE, parameters: [
                    'peer' => $peer,
                    'signature_uuid' => $signatureUuid,
                    'signature' => $signature,
                    'sha512' => $sha512,
                    'signature_time' => $signatureTime
                ])
            )->getResponse()->getResult()) ?? SignatureVerificationStatus::ERROR;
        }

        /**
         * Accepts an encrypted channel using the provided public encryption key.
         *
         * @param string $channelUuid The UUID of the channel to accept
         * @param string $publicEncryptionKey The public encryption key to use for the channel
         * @param PeerAddress|string|null $identifiedAs Optional identifier for the peer (PeerAddress object, string, or null)
         *
         * @return bool Returns true if the channel was successfully accepted, false otherwise
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function encryptionAcceptChannel(string $channelUuid, string $publicEncryptionKey, PeerAddress|string|null $identifiedAs=null): bool
        {
            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_ACCEPT_CHANNEL, parameters: [
                    'channel_uuid' => $channelUuid,
                    'public_encryption_key' => $publicEncryptionKey
                ]), true, $identifiedAs
            )->getResponse()->getResult();
        }

        /**
         * Acknowledges an encrypted message in a specific channel.
         *
         * This method is used to confirm the successful delivery and decryption of a message
         * within an encrypted communication channel. It allows the client to signal that a
         * particular message, identified by its UUID, has been successfully processed.
         *
         * @param string $channelUuid The UUID of the channel in which the message was sent.
         * @param string $messageUuid The UUID of the message being acknowledged.
         * @param PeerAddress|string|null $identifiedAs Optional. The peer address or identifier of the recipient acknowledging the message.
         *                                             It can be a PeerAddress object, a string representation of the address, or null if not applicable.
         *
         * @return bool True if the acknowledgement was successful, false otherwise.
         * @throws RpcException Thrown if there was an error with the RPC request.
         */
        public function encryptionChannelAcknowledgeMessage(string $channelUuid, string $messageUuid, PeerAddress|string|null $identifiedAs=null): bool
        {
            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_CHANNEL_ACKNOWLEDGE_MESSAGE, parameters: [
                    'channel_uuid' => $channelUuid,
                    'message_uuid' => $messageUuid
                ]), true, $identifiedAs
            )->getResponse()->getResult();
        }

        /**
         * Acknowledges multiple messages in an encryption channel by sending multiple requests at once
         *
         * @param string $channelUuid The UUID of the encryption channel.
         * @param array $messageUuids An array of message UUIDs to acknowledge.
         * @param PeerAddress|string|null $identifiedAs Optional peer address or identifier.
         * @return array An array containing the results of the acknowledgement operation.
         * @throws RpcException Thrown if there was an error with the RPC request.
         */
        public function encryptionChannelAcknowledgeMessages(string $channelUuid, array $messageUuids, PeerAddress|string|null $identifiedAs=null): array
        {
            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequests(array_map(fn($messageUuid) => new RpcRequest(StandardMethods::ENCRYPTION_CHANNEL_ACKNOWLEDGE_MESSAGE, parameters: [
                'channel_uuid' => $channelUuid,
                'message_uuid' => $messageUuid
            ]), $messageUuids), $identifiedAs);
        }

        /**
         * Checks if an encryption channel exists for the given channel UUID.
         *
         * @param string $channelUuid The UUID of the channel to check.
         *
         * @return bool True if an encryption channel exists, false otherwise.
         * @throws RpcException Thrown if there was an error with the RPC request.
         */
        public function encryptionChannelExists(string $channelUuid): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_CHANNEL_EXISTS, parameters: [
                    'channel_uuid' => $channelUuid
                ])
            )->getResponse()->getResult();
        }

        /**
         * Rejects a message from the encryption channel
         *
         * @param string $channelUuid The channel UUID used for communication
         * @param string $messageUuid The Message UUID to reject
         * @param PeerAddress|string|null $identifiedAs Optional. The requesting peer that's rejecting this
         * @return bool Returns True on success, False if not applicable
         * @throws RpcException Thrown when there is an error
         */
        public function encryptionChannelRejectMessage(string $channelUuid, string $messageUuid, PeerAddress|string|null $identifiedAs): bool
        {
            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_CHANNEL_REJECT_MESSAGE, parameters: [
                    'channel_uuid' => $channelUuid,
                    'message_uuid' => $messageUuid,
                ]), true, $identifiedAs
            )->getResponse()->getResult();
        }

        /**
         * Sends an encrypted message to a specific channel.
         *
         * @param string $channelUuid The UUID of the channel to send the message to.
         * @param string $checksum The checksum of the encrypted data for integrity verification.
         * @param string $data The encrypted data to be sent.
         * @param PeerAddress|string|null $identifiedAs The peer address or identifier of the sender (optional).
         * @param string|null $messageUuid The UUID of the message (optional). If null, a UUID will be generated.
         * @param int|null $timestamp The timestamp of the message (optional). If null, the current timestamp will be used.
         * @return string The UUID of the sent message.
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function encryptionChannelSend(string $channelUuid, string $checksum, string $data, PeerAddress|string|null $identifiedAs=null, ?string $messageUuid=null, ?int $timestamp=null): string
        {
            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_CHANNEL_SEND, parameters: [
                    'channel_uuid' => $channelUuid,
                    'checksum' => $checksum,
                    'data' => $data,
                    'uuid' => $messageUuid,
                    'timestamp' => $timestamp
                ]), true, $identifiedAs
            )->getResponse()->getResult();
        }

        /**
         * Closes an encrypted communication channel.
         *
         * @param string $channelUuid The UUID of the channel to close.
         * @param PeerAddress|string|null $identifiedAs Optional identifier for the peer, either a PeerAddress object, a string representation, or null if not applicable.
         * @return bool True if the channel was successfully closed, false otherwise.
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function encryptionCloseChannel(string $channelUuid, PeerAddress|string|null $identifiedAs=null): bool
        {
            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_CLOSE_CHANNEL, parameters: [
                    'channel_uuid' => $channelUuid
                ]), true, $identifiedAs
            )->getResponse()->getResult();
        }

        /**
         * Creates an encrypted channel with the specified peer.
         *
         * @param string|PeerAddress $receivingPeer The peer to create the channel with. Can be a PeerAddress object or a string representation of the peer's address.
         * @param string $publicEncryptionKey The public encryption key to use for the channel.
         * @param string|null $channelUuid Optional UUID for the channel. If null, a UUID will be generated.
         * @param PeerAddress|string|null $identifiedAs Optional peer address or string to identify the channel as.
         * @return string The UUID of the created channel.
         * @throws RpcException Thrown if there was an error with the RPC requests
         */
        public function encryptionCreateChannel(string|PeerAddress $receivingPeer, string $publicEncryptionKey, ?string $channelUuid=null, PeerAddress|string|null $identifiedAs=null): string
        {
            if($receivingPeer instanceof PeerAddress)
            {
                $receivingPeer = $receivingPeer->getAddress();
            }

            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_CREATE_CHANNEL, parameters: [
                    'receiving_peer' => $receivingPeer,
                    'public_encryption_key' => $publicEncryptionKey,
                    'channel_uuid' => $channelUuid
                ]), true, $identifiedAs
            )->getResponse()->getResult();
        }

        /**
         * Declines an encryption channel request.
         *
         * @param string $channelUuid The UUID of the channel to decline.
         * @param PeerAddress|string|null $identifiedAs The peer address or identifier of the peer that initiated the channel request.
         * @return bool True if the channel decline was successful, false otherwise.
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function encryptionDeclineChannel(string $channelUuid, PeerAddress|string|null $identifiedAs=null): bool
        {
            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_DECLINE_CHANNEL, parameters: [
                    'channel_uuid' => $channelUuid
                ]), true, $identifiedAs
            )->getResponse()->getResult();
        }

        /**
         * Retrieves an encryption channel by its UUID.
         *
         * @param string $channelUuid The UUID of the encryption channel to retrieve.
         * @return EncryptionChannel The EncryptionChannel object associated with the given UUID.
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function encryptionGetChannel(string $channelUuid): EncryptionChannel
        {
            return new EncryptionChannel($this->sendRequest(
                new RpcRequest(StandardMethods::ENCRYPTION_GET_CHANNEL, parameters: [
                    'channel_uuid' => $channelUuid
                ])
            )->getResponse()->getResult());
        }

        /**
         * Accepts the community guidelines, returns True if the guidelines were accepted.
         *
         * @return true Returns True if the guidelines were accepted
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function acceptCommunityGuidelines(): true
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::ACCEPT_COMMUNITY_GUIDELINES)
            )->getResponse()->getResult();
        }

        /**
         * Accepts the privacy policy, returns True if the privacy policy was accepted.
         *
         * @return true Returns True if the privacy policy was accepted
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function acceptPrivacyPolicy(): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::ACCEPT_PRIVACY_POLICY)
            )->getResponse()->getResult();
        }

        /**
         * Accepts the terms of service, returns True if the terms of service were accepted.
         *
         * @return true Returns True if the terms of service were accepted
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function acceptTermsOfService(): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::ACCEPT_TERMS_OF_SERVICE)
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the community guidelines, returns the guidelines as a ServerDocument object.
         *
         * @return ServerDocument The guidelines as a ServerDocument object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function getCommunityGuidelines(): ServerDocument
        {
            return new ServerDocument($this->sendRequest(
                new RpcRequest(StandardMethods::GET_COMMUNITY_GUIDELINES)
            )->getResponse()->getResult());
        }

        /**
         * Retrieves the privacy policy, returns the policy as a ServerDocument object.
         *
         * @return ServerDocument The policy as a ServerDocument object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function getPrivacyPolicy(): ServerDocument
        {
            return new ServerDocument($this->sendRequest(
                new RpcRequest(StandardMethods::GET_PRIVACY_POLICY)
            )->getResponse()->getResult());
        }

        /**
         * Retrieves the terms of service, returns the terms as a ServerDocument object.
         *
         * @return ServerDocument The terms as a ServerDocument object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function getTermsOfService(): ServerDocument
        {
            return new ServerDocument($this->sendRequest(
                new RpcRequest(StandardMethods::GET_TERMS_OF_SERVICE)
            )->getResponse()->getResult());
        }

        /**
         * Adds a new information field to the peer's profile, returns True if the field was added.
         *
         * @param InformationFieldName|string $field The name of the field to add
         * @param string $value The value of the field
         * @param PrivacyState|string|null $privacy Optional. The privacy state of the field
         * @return bool Returns True if the field was added
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsAddInformationField(InformationFieldName|string $field, string $value, null|PrivacyState|string $privacy): bool
        {
            if($field instanceof InformationFieldName)
            {
                $field = $field->value;
            }

            if($privacy instanceof PrivacyState)
            {
                $privacy = $privacy->value;
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_ADD_INFORMATION_FIELD, parameters: [
                    'field' => $field,
                    'value' => $value,
                    'privacy' => $privacy
                ])
            )->getResponse()->getResult();
        }

        /**
         * Adds a new public signature to the peer's profile, returns the UUID of the signature.
         *
         * @param string $publicKey The public key of the signature
         * @param string|null $name Optional. The name of the signature
         * @param int|null $expires Optional. The expiration time of the signature
         * @return string The UUID of the signature
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsAddSignature(string $publicKey, ?string $name=null, ?int $expires=null): string
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_ADD_SIGNATURE, parameters: [
                    'public_key' => $publicKey,
                    'name' => $name,
                    'expires' => $expires
                ])
            )->getResponse()->getResult();
        }

        /**
         * Deletes an information field from the peer's profile, returns True if the field was deleted.
         *
         * @param InformationFieldName|string $field The name of the field to delete
         * @return bool Returns True if the field was deleted
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsDeleteInformationField(InformationFieldName|string $field): bool
        {
            if($field instanceof InformationFieldName)
            {
                $field = $field->value;
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_DELETE_INFORMATION_FIELD, parameters: [
                    'field' => $field
                ])
            )->getResponse()->getResult();
        }

        /**
         * Deletes the OTP from the peer's profile, returns True if the OTP was deleted.
         *
         * @param string|null $password Optional. Required if a password is set, this is used to verify the operation
         * @param bool $hash Optional. Whether to hash the password
         * @return bool Returns True if the OTP was deleted
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsDeleteOtp(?string $password=null, bool $hash=true): bool
        {
            if($hash && $password != null)
            {
                $password = hash('sha512', $password);
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_DELETE_OTP, parameters: [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }

        /**
         * Deletes the password from the peer's profile, returns True if the password was deleted.
         *
         * @return bool Returns True if the password was deleted
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsDeletePassword(): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_DELETE_PASSWORD)
            )->getResponse()->getResult();
        }

        /**
         * Deletes a signature from the peer's profile, returns True if the signature was deleted.
         *
         * @param string $uuid The UUID of the signature to delete
         * @return bool Returns True if the signature was deleted
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsDeleteSignature(string $uuid): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_DELETE_SIGNATURE, parameters: [
                    'uuid' => $uuid
                ])
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the value of an information field from the peer's profile, returns the value of the field as an
         * InformationFieldState object.
         *
         * @param InformationFieldName|string $field The name of the field to retrieve
         * @return InformationFieldState The value of the field as an InformationFieldState object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsGetInformationField(InformationFieldName|string $field): InformationFieldState
        {
            if($field instanceof InformationFieldName)
            {
                $field = $field->value;
            }

            return new InformationFieldState($this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_GET_INFORMATION_FIELD, parameters: [
                    'field' => $field
                ])
            )->getResponse()->getResult());
        }

        /**
         * Retrieves a list of information fields from the peer's profile, returns an array of InformationFieldState objects.
         *
         * @return InformationFieldState[] An array of InformationFieldState objects
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsGetInformationFields(): array
        {
            return array_map(fn($informationFieldState) => new InformationFieldState($informationFieldState), $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_GET_INFORMATION_FIELDS)
            )->getResponse()->getResult());
        }

        /**
         * Returns the existing Signature of a signature UUID associated with the peer's profile
         *
         * @param string $uuid The UUID of the signature to retrieve
         * @return Signature The Signature object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsGetSignature(string $uuid): Signature
        {
            return new Signature($this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_GET_SIGNATURE, parameters: [
                    'uuid' => $uuid
                ])
            )->getResponse()->getResult());
        }

        /**
         * Retrieves a list of public signatures from the peer's profile, returns an array of Signature objects.
         *
         * @return Signature[] An array of Signature objects
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsGetSignatures(): array
        {
            return array_map(fn($signatures) => new Signature($signatures), $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_GET_INFORMATION_FIELDS)
            )->getResponse()->getResult());
        }

        /**
         * Checks if an information field exists in the peer's profile, returns True if the field exists.
         *
         * @param InformationFieldName|string $field The name of the field to check
         * @return bool Returns True if the field exists
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsInformationFieldExists(InformationFieldName|string $field): bool
        {
            if($field instanceof InformationFieldName)
            {
                $field = $field->value;
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_INFORMATION_FIELD_EXISTS, parameters: [
                    'field' => $field
                ])
            )->getResponse()->getResult();
        }

        /**
         * Sets the OTP for the peer's profile, returns True if the OTP was set.
         *
         * @param string|null $password Optional. If a password is set to the account, this is used to verify the operation
         * @param bool $hash Optional. Whether to hash the password
         * @return string Returns True if the OTP was set
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsSetOtp(?string $password=null, bool $hash=true): string
        {
            if($hash && $password != null)
            {
                $password = hash('sha512', $password);
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_SET_OTP, parameters: [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }

        /**
         * Sets the password for the peer's profile, returns True if the password was set.
         *
         * @param string $password The password to set
         * @param bool $hash Optional. Whether to hash the password
         * @return bool Returns True if the password was set
         * @throws CryptographyException Thrown if there was an error while hashing the password
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsSetPassword(string $password, bool $hash=true): bool
        {
            if($hash)
            {
                $password = Cryptography::hashPassword($password);
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_SET_PASSWORD, parameters: [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }

        /**
         * Checks if a signature exists in the peer's profile, returns True if the signature exists.
         *
         * @param string $uuid The UUID of the signature to check for it's existence
         * @return bool Returns True if the signature exists, False otherwise
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsSignatureExists(string $uuid): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_SIGNATURE_EXISTS, parameters: [
                    'uuid' => $uuid
                ])
            )->getResponse()->getResult();
        }

        /**
         * Updates the value of an information field in the peer's profile, returns True if the field was updated.
         *
         * @param InformationFieldName|string $field The name of the field to update
         * @param string $value The value to update the field to
         * @return bool Returns True if the field was updated
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsUpdateInformationField(InformationFieldName|string $field, string $value): bool
        {
            if($field instanceof InformationFieldName)
            {
                $field = $field->value;
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_UPDATE_INFORMATION_FIELD, parameters: [
                    'field' => $field,
                    'value' => $value,
                ])
            )->getResponse()->getResult();
        }

        /**
         * Updates the privacy of an information field in the peer's profile, returns True if the field was updated.
         *
         * @param InformationFieldName|string $field The name of the field to update
         * @param PrivacyState|string $privacy The privacy state to update the field to
         * @return bool Returns True if the field was updated
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsUpdateInformationPrivacy(InformationFieldName|string $field, PrivacyState|string $privacy): bool
        {
            if($field instanceof InformationFieldName)
            {
                $field = $field->value;
            }

            if($privacy instanceof PrivacyState)
            {
                $privacy = $privacy->value;
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_UPDATE_INFORMATION_PRIVACY, parameters: [
                    'field' => $field,
                    'privacy' => $privacy
                ])
            )->getResponse()->getResult();
        }

        /**
         * Updates the existing password configuration on the account, requires the existing password to verify
         * the operation. Returns True upon success
         *
         * @param string $password The new password to set
         * @param string $existingPassword The existing password already tied to the account
         * @param bool $hash If True, the password inputs will be hashed, false will be sent as is.
         * @return bool Returns True if the password was updated successfully
         * @throws CryptographyException Thrown if there was an error while hashing the passwords
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function settingsUpdatePassword(string $password, string $existingPassword, bool $hash=true): bool
        {
            if($hash)
            {
                $existingPassword = hash('sha512', $password);
                $password = Cryptography::hashPassword($password);
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_UPDATE_PASSWORD, parameters: [
                    'password' => $password,
                    'existing_password' => $existingPassword
                ])
            )->getResponse()->getResult();
        }

        /**
         * Submits an answer for an image captcha problem, returns True if the answer is correct, False otherwise
         *
         * @param string $answer The answer for the captcha
         * @return bool Returns True if the answer is correct, False otherwise.
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function verificationAnswerImageCaptcha(string $answer): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_ANSWER_IMAGE_CAPTCHA, parameters: [
                    'answer' => $answer
                ])
            )->getResponse()->getResult();
        }

        /**
         * Authenticates the verification process, returns True if the authentication is successful.
         * This method is usually used for server-to-server communication.
         *
         * @return bool Returns True if the authentication is successful
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function verificationAuthenticate(): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_AUTHENTICATE)
            )->getResponse()->getResult();
        }

        /**
         * Retrieves an image captcha problem, returns the problem as an ImageCaptchaVerification object.
         *
         * @return ImageCaptchaVerification The problem as an ImageCaptchaVerification object
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function verificationGetImageCaptcha(): ImageCaptchaVerification
        {
            return new ImageCaptchaVerification($this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_GET_IMAGE_CAPTCHA)
            )->getResponse()->getResult());
        }

        /**
         * Verifies a one-time password (OTP) authentication code, returns True if the code is correct, False otherwise.
         *
         * @param int $code The OTP code to verify
         * @return bool Returns True if the code is correct, False otherwise
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function verificationOtpAuthentication(int $code): bool
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_OTP_AUTHENTICATION, parameters: [
                    'code' => $code
                ])
            )->getResponse()->getResult();
        }

        /**
         * Verifies a password authentication, returns True if the password is correct, False otherwise.
         *
         * @param string $password The password to verify
         * @param bool $hash Optional. Whether to hash the password
         * @return bool Returns True if the password is correct, False otherwise
         * @throws RpcException Thrown if there was an error with the RPC request
         */
        public function verificationPasswordAuthentication(string $password, bool $hash=true): bool
        {
            if($hash)
            {
                $password = hash('sha512', $password);
            }

            return $this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_PASSWORD_AUTHENTICATION, parameters: [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }
    }