<?php

    namespace Socialbox\Managers;

    use DateMalformedStringException;
    use DateTime;
    use InvalidArgumentException;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Logger;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\SessionState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Objects\Database\PeerDatabaseRecord;
    use Socialbox\Objects\Database\SessionRecord;
    use Socialbox\Objects\KeyPair;
    use Symfony\Component\Uid\Uuid;

    class SessionManager
    {
        /**
         * Creates a new session for a given peer and client details, and stores it in the database.
         *
         * @param PeerDatabaseRecord $peer The peer record for which the session is being created.
         * @param string $clientName The name of the client application.
         * @param string $clientVersion The version of the client application.
         * @param string $clientPublicSigningKey The client's public signing key, which must be a valid Ed25519 key.
         * @param string $clientPublicEncryptionKey The client's public encryption key, which must be a valid X25519 key.
         * @param KeyPair $serverEncryptionKeyPair The server's key pair for encryption, including both public and private keys.
         * @return string The UUID of the newly created session.
         * @throws InvalidArgumentException If the provided public signing key or encryption key is invalid.
         * @throws DatabaseOperationException If there is an error during the session creation in the database.
         */
        public static function createSession(PeerDatabaseRecord $peer, string $clientName, string $clientVersion, string $clientPublicSigningKey, string $clientPublicEncryptionKey, KeyPair $serverEncryptionKeyPair): string
        {
            if($clientPublicSigningKey === '' || Cryptography::validatePublicSigningKey($clientPublicSigningKey) === false)
            {
                throw new InvalidArgumentException('The public key is not a valid Ed25519 public key');
            }

            if($clientPublicEncryptionKey === '' || Cryptography::validatePublicEncryptionKey($clientPublicEncryptionKey) === false)
            {
                throw new InvalidArgumentException('The public key is not a valid X25519 public key');
            }

            $uuid = Uuid::v4()->toRfc4122();
            $flags = [];

            // TODO: Update this to support `host` peers
            if($peer->isExternal())
            {
                $flags[] = SessionFlags::AUTHENTICATION_REQUIRED;
                $flags[] = SessionFlags::VER_AUTHENTICATION;
            }
            else if($peer->isEnabled())
            {
                $flags[] = SessionFlags::AUTHENTICATION_REQUIRED;

                if(PasswordManager::usesPassword($peer->getUuid()))
                {
                    $flags[] = SessionFlags::VER_PASSWORD;
                }

                if(Configuration::getRegistrationConfiguration()->isImageCaptchaVerificationRequired())
                {
                    $flags[] = SessionFlags::VER_IMAGE_CAPTCHA;
                }
            }
            else
            {
                $flags[] = SessionFlags::REGISTRATION_REQUIRED;

                if(Configuration::getRegistrationConfiguration()->isDisplayNameRequired())
                {
                    $flags[] = SessionFlags::SET_DISPLAY_NAME;
                }

                if(Configuration::getRegistrationConfiguration()->isDisplayPictureRequired())
                {
                    $flags[] = SessionFlags::SET_DISPLAY_PICTURE;
                }

                if(Configuration::getRegistrationConfiguration()->isImageCaptchaVerificationRequired())
                {
                    $flags[] = SessionFlags::VER_IMAGE_CAPTCHA;
                }

                if(Configuration::getRegistrationConfiguration()->isPasswordRequired())
                {
                    $flags[] = SessionFlags::SET_PASSWORD;
                }

                if(Configuration::getRegistrationConfiguration()->isOtpRequired())
                {
                    $flags[] = SessionFlags::SET_OTP;
                }

                if(Configuration::getRegistrationConfiguration()->isAcceptPrivacyPolicyRequired())
                {
                    $flags[] = SessionFlags::VER_PRIVACY_POLICY;
                }

                if(Configuration::getRegistrationConfiguration()->isAcceptTermsOfServiceRequired())
                {
                    $flags[] = SessionFlags::VER_TERMS_OF_SERVICE;
                }

                if(Configuration::getRegistrationConfiguration()->isAcceptCommunityGuidelinesRequired())
                {
                    $flags[] = SessionFlags::VER_COMMUNITY_GUIDELINES;
                }
            }

            if(count($flags) > 0)
            {
                $implodedFlags = SessionFlags::toString($flags);
            }
            else
            {
                $implodedFlags = null;
            }

            $peerUuid = $peer->getUuid();

            try
            {
                $statement = Database::getConnection()->prepare("INSERT INTO sessions (uuid, peer_uuid, client_name, client_version, client_public_signing_key, client_public_encryption_key, server_public_encryption_key, server_private_encryption_key, flags) VALUES (:uuid, :peer_uuid, :client_name, :client_version, :client_public_signing_key, :client_public_encryption_key, :server_public_encryption_key, :server_private_encryption_key, :flags)");
                $statement->bindParam(':uuid', $uuid);
                $statement->bindParam(':peer_uuid', $peerUuid);
                $statement->bindParam(':client_name', $clientName);
                $statement->bindParam(':client_version', $clientVersion);
                $statement->bindParam(':client_public_signing_key', $clientPublicSigningKey);
                $statement->bindParam(':client_public_encryption_key', $clientPublicEncryptionKey);
                $serverPublicEncryptionKey = $serverEncryptionKeyPair->getPublicKey();
                $statement->bindParam(':server_public_encryption_key', $serverPublicEncryptionKey);
                $serverPrivateEncryptionKey = $serverEncryptionKeyPair->getPrivateKey();
                $statement->bindParam(':server_private_encryption_key', $serverPrivateEncryptionKey);
                $statement->bindParam(':flags', $implodedFlags);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to create a session on the database', $e);
            }

            return $uuid;
        }

        /**
         * Checks if a session with the given UUID exists in the database.
         *
         * @param string $uuid The UUID of the session to check.
         * @return bool True if the session exists, false otherwise.
         * @throws DatabaseOperationException If there is an error executing the database query.
         */
        public static function sessionExists(string $uuid): bool
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT COUNT(*) FROM sessions WHERE uuid=?");
                $statement->bindParam(1, $uuid);
                $statement->execute();
                $result = $statement->fetchColumn();

                return $result > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if the session exists', $e);
            }
        }

        /**
         * Retrieves a session record by its unique identifier.
         *
         * @param string $uuid The unique identifier of the session.
         * @return SessionRecord The session record corresponding to the given UUID.
         * @throws DatabaseOperationException If the session record cannot be found or if there is an error during retrieval.
         * @throws StandardRpcException
         */
        public static function getSession(string $uuid): SessionRecord
        {
            Logger::getLogger()->verbose(sprintf("Retrieving session %s from the database", $uuid));

            try
            {
                $statement = Database::getConnection()->prepare("SELECT * FROM sessions WHERE uuid=?");
                $statement->bindParam(1, $uuid);
                $statement->execute();
                $data = $statement->fetch(PDO::FETCH_ASSOC);

                if ($data === false)
                {
                    throw new StandardRpcException(sprintf("The requested session '%s' does not exist", $uuid), StandardError::SESSION_NOT_FOUND);
                }

                // Convert the timestamp fields to DateTime objects
                $data['created'] = new DateTime($data['created']);
                if(isset($data['last_request']) && $data['last_request'] !== null)
                {
                    $data['last_request'] = new DateTime($data['last_request']);
                }
                else
                {
                    $data['last_request'] = null;
                }

                return SessionRecord::fromArray($data);

            }
            catch (PDOException | DateMalformedStringException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to retrieve session record %s', $uuid), $e);
            }
        }

        /**
         * Updates the last request timestamp for a given session by its UUID.
         *
         * @param string $uuid The UUID of the session to be updated.
         * @return void
         * @throws DatabaseOperationException
         */
        public static function updateLastRequest(string $uuid): void
        {
            Logger::getLogger()->verbose(sprintf("Updating last request timestamp for session %s", $uuid));

            try
            {
                $formattedTime = (new DateTime('@' . time()))->format('Y-m-d H:i:s');
                $statement = Database::getConnection()->prepare("UPDATE sessions SET last_request=? WHERE uuid=?");
                $statement->bindValue(1, $formattedTime, PDO::PARAM_STR);
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch (PDOException | DateMalformedStringException $e)
            {
                throw new DatabaseOperationException('Failed to update last request', $e);
            }
        }

        /**
         * Updates the state of a session given its UUID.
         *
         * @param string $uuid The unique identifier of the session to update.
         * @param SessionState $state The new state to be set for the session.
         * @return void No return value.
         * @throws DatabaseOperationException
         */
        public static function updateState(string $uuid, SessionState $state): void
        {
            Logger::getLogger()->verbose(sprintf("Updating state of session %s to %s", $uuid, $state->value));

            try
            {
                $state_value = $state->value;
                $statement = Database::getConnection()->prepare('UPDATE sessions SET state=? WHERE uuid=?');
                $statement->bindParam(1, $state_value);
                $statement->bindParam(2, $uuid);

                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update session state', $e);
            }
        }

        /**
         * Updates the encryption keys and session state for a specific session UUID in the database.
         *
         * @param string $uuid The unique identifier for the session to update.
         * @param string $privateSharedSecret The private shared secret to secure communication.
         * @param string $clientEncryptionKey The client's encryption key used for transport security.
         * @param string $serverEncryptionKey The server's encryption key used for transport security.
         * @return void
         * @throws DatabaseOperationException If an error occurs during the database operation.
         */
        public static function setEncryptionKeys(string $uuid, string $privateSharedSecret, string $clientEncryptionKey, string $serverEncryptionKey): void
        {
            Logger::getLogger()->verbose(sprintf('Setting the encryption key for %s', $uuid));

            try
            {
                $state_value = SessionState::ACTIVE->value;
                $statement = Database::getConnection()->prepare('UPDATE sessions SET state=:state, private_shared_secret=:private_shared_secret, client_transport_encryption_key=:client_transport_encryption_key, server_transport_encryption_key=:server_transport_encryption_key WHERE uuid=:uuid');
                $statement->bindParam(':state', $state_value);
                $statement->bindParam(':private_shared_secret', $privateSharedSecret);
                $statement->bindParam(':client_transport_encryption_key', $clientEncryptionKey);
                $statement->bindParam(':server_transport_encryption_key', $serverEncryptionKey);
                $statement->bindParam(':uuid', $uuid);

                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to set the encryption key', $e);
            }
        }

        /**
         * Retrieves the flags associated with a specific session.
         *
         * @param string $uuid The UUID of the session to retrieve flags for.
         * @return SessionFlags[] An array of flags associated with the specified session.
         * @throws StandardRpcException If the specified session does not exist.
         * @throws DatabaseOperationException If there
         */
        private static function getFlags(string $uuid): array
        {
            Logger::getLogger()->verbose(sprintf("Retrieving flags for session %s", $uuid));

            try
            {
                $statement = Database::getConnection()->prepare("SELECT flags FROM sessions WHERE uuid=?");
                $statement->bindParam(1, $uuid);
                $statement->execute();
                $data = $statement->fetch(PDO::FETCH_ASSOC);

                if ($data === false)
                {
                    throw new StandardRpcException(sprintf("The requested session '%s' does not exist", $uuid), StandardError::SESSION_NOT_FOUND);
                }

                return SessionFlags::fromString($data['flags']);
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to retrieve flags for session %s', $uuid), $e);
            }
        }

        /**
         * Adds the specified flags to the session identified by the given UUID.
         *
         * @param string $uuid The unique identifier of the session to which the flags will be added.
         * @param array $flags The flags to add to the session.
         * @return void
         * @throws DatabaseOperationException|StandardRpcException If there is an error while updating the session in the database.
         */
        public static function addFlags(string $uuid, array $flags): void
        {
            Logger::getLogger()->verbose(sprintf("Adding flags to session %s", $uuid));

            // First get the existing flags
            $existingFlags = self::getFlags($uuid);

            // Merge the new flags with the existing ones
            $flags = array_unique(array_merge($existingFlags, $flags));

            try
            {
                $statement = Database::getConnection()->prepare("UPDATE sessions SET flags=? WHERE uuid=?");
                $statement->bindValue(1, SessionFlags::toString($flags));
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to add flags to session', $e);
            }
        }

        /**
         * Removes specified flags from the session associated with the given UUID.
         *
         * @param string $uuid The UUID of the session from which the flags will be removed.
         * @param SessionFlags[] $flags An array of flags to be removed from the session.
         * @return void
         * @throws DatabaseOperationException|StandardRpcException If there is an error while updating the session in the database.
         */
        public static function removeFlags(string $uuid, array $flags): void
        {
            Logger::getLogger()->verbose(sprintf("Removing flags from session %s", $uuid));

            $existingFlags = array_map(fn(SessionFlags $flag) => $flag->value, self::getFlags($uuid));
            $flagsToRemove = array_map(fn(SessionFlags $flag) => $flag->value, $flags);
            $updatedFlags = array_diff($existingFlags, $flagsToRemove);
            $flagString = SessionFlags::toString(array_map(fn(string $value) => SessionFlags::from($value), $updatedFlags));

            try
            {
                // Update the session flags in the database
                $statement = Database::getConnection()->prepare("UPDATE sessions SET flags=? WHERE uuid=?");
                $statement->bindValue(1, $flagString); // Use the stringified updated flags
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to remove flags from session', $e);
            }
        }

        /**
         * Updates the authentication status for the specified session.
         *
         * @param string $uuid The unique identifier of the session to be updated.
         * @param bool $authenticated The authentication status to set for the session.
         * @return void
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function setAuthenticated(string $uuid, bool $authenticated): void
        {
            Logger::getLogger()->verbose(sprintf("Setting session %s as authenticated: %s", $uuid, $authenticated ? 'true' : 'false'));

            try
            {
                $statement = Database::getConnection()->prepare("UPDATE sessions SET authenticated=? WHERE uuid=?");
                $statement->bindParam(1, $authenticated);
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update authenticated peer', $e);
            }
        }

        /**
         * Marks the session as complete if all necessary conditions are met.
         *
         * @param SessionRecord $session The session record to evaluate and potentially mark as complete.
         * @param array $flagsToRemove An array of flags to remove from the session if it is marked as complete.
         * @return void
         * @throws DatabaseOperationException If there is an error while updating the session in the database.
         * @throws StandardRpcException If the session record cannot be found or if there is an error during retrieval.
         */
        public static function updateFlow(SessionRecord $session, array $flagsToRemove=[]): void
        {
            // Don't do anything if the session is already authenticated
            if (!$session->flagExists(SessionFlags::AUTHENTICATION_REQUIRED) && !$session->flagExists(SessionFlags::REGISTRATION_REQUIRED))
            {
                return;
            }

            // Don't do anything if the flags to remove are not present
            if(!$session->flagExists($flagsToRemove))
            {
                return;
            }

            // Remove & update the session flags
            self::removeFlags($session->getUuid(), $flagsToRemove);
            $session = self::getSession($session->getUuid());

            // Check if all registration/authentication requirements are met
            if(SessionFlags::isComplete($session->getFlags()))
            {
                SessionManager::removeFlags($session->getUuid(), [SessionFlags::REGISTRATION_REQUIRED, SessionFlags::AUTHENTICATION_REQUIRED]); // Remove the registration/authentication flags
                SessionManager::setAuthenticated($session->getUuid(), true); // Mark the session as authenticated
                RegisteredPeerManager::enablePeer($session->getPeerUuid()); // Enable the peer
            }
        }
    }