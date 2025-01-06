<?php

    namespace Socialbox\Managers;

    use DateTime;
    use InvalidArgumentException;
    use ncc\ThirdParty\Symfony\Uid\UuidV4;
    use PDOException;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Enums\SigningKeyState;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\SigningKeyRecord;

    class SigningKeysManager
    {
        /**
         * Retrieves the state of a signing key identified by its UUID.
         *
         * @param string $uuid The UUID of the signing key whose state is to be retrieved.
         * @return SigningKeyState The state of the signing key. Returns SigningKeyState::EXPIRED if the key is expired,
         *                         SigningKeyState::NOT_FOUND if the key does not exist, or the state as defined in the database.
         * @throws DatabaseOperationException If an error occurs during the database operation.
         */
        public static function getSigningKeyState(string $uuid): SigningKeyState
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT state, expires FROM signing_keys WHERE uuid=:uuid");
                $statement->bindParam(':uuid', $uuid);
                $statement->execute();

                if($row = $statement->fetch())
                {
                    if(is_int($row['expires']) && $row['expires'] < time())
                    {
                        return SigningKeyState::EXPIRED;
                    }

                    if($row['expires'] instanceof DateTime && $row['expires'] < new DateTime())
                    {
                        return SigningKeyState::EXPIRED;
                    }

                    return SigningKeyState::tryFrom($row['state']) ?? SigningKeyState::NOT_FOUND;
                }
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get the signing key state from the database', $e);
            }

            return SigningKeyState::NOT_FOUND;
        }

        /**
         * Retrieves the count of signing keys associated with a specific peer UUID.
         *
         * @param string $peerUuid The UUID of the peer for which to count the signing keys.
         * @return int The number of signing keys associated with the given peer UUID.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function getSigningKeyCount(string $peerUuid): int
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT COUNT(*) FROM signing_keys WHERE peer_uuid=:peer_uuid");
                $statement->bindParam(':peer_uuid', $peerUuid);
                $statement->execute();

                return $statement->fetchColumn();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get the signing key count from the database', $e);
            }
        }

        /**
         * Adds a signing key to the database for a specific peer.
         *
         * @param string $peerUuid The unique identifier of the peer associated with the signing key.
         * @param string $publicKey The public signing key to be added. Must be valid according to the Cryptography::validatePublicSigningKey method.
         * @param int|null $expires Optional expiration timestamp for the signing key. Can be null if the key does not expire.
         * @param string|null $name Optional name associated with the signing key. Must not exceed 64 characters in length.
         * @throws DatabaseOperationException If the operation to add the signing key to the database fails.
         * @return string The UUID of the newly added signing key.
         */
        public static function addSigningKey(string $peerUuid, string $publicKey, ?int $expires=null, ?string $name=null): string
        {
            if(!Cryptography::validatePublicSigningKey($publicKey))
            {
                throw new InvalidArgumentException('The public key is invalid');
            }

            if(strlen($name) > 64)
            {
                throw new InvalidArgumentException('The name is too long');
            }

            if($name !== null && empty($name))
            {
                throw new InvalidArgumentException('The name cannot be empty');
            }

            if($expires !== null && $expires < time())
            {
                throw new InvalidArgumentException('The expiration time is in the past');
            }

            // At least more than 1 hour
            if($expires !== null && $expires < time() + 3600)
            {
                throw new InvalidArgumentException('The expiration time is too soon, must be at least 1 hour in the future');
            }


            $uuid = UuidV4::v4()->toRfc4122();

            try
            {
                $statement = Database::getConnection()->prepare("INSERT INTO signing_keys (uuid, peer_uuid, public_key, expires, name) VALUES (:uuid, :peer_uuid, :public_key, :expires, :name)");
                $statement->bindParam(':uuid', $uuid);
                $statement->bindParam(':peer_uuid', $peerUuid);
                $statement->bindParam(':public_key', $publicKey);
                $statement->bindParam(':expires', $expires);
                $statement->bindParam(':name', $name);
                $statement->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to add a signing key to the database', $e);
            }

            return $uuid;
        }

        /**
         * Updates the state of a signing key in the database identified by its UUID.
         *
         * @param string $uuid The unique identifier of the signing key to update.
         * @param SigningKeyState $state The new state to set for the signing key.
         * @return void
         * @throws DatabaseOperationException
         */
        public static function updateSigningKeyState(string $uuid, SigningKeyState $state): void
        {
            $state = $state->value;

            try
            {
                $statement = Database::getConnection()->prepare("UPDATE signing_keys SET state=:state WHERE uuid=:uuid");
                $statement->bindParam(':state', $state);
                $statement->bindParam(':uuid', $uuid);
                $statement->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the signing key state in the database', $e);
            }
        }

        /**
         * Retrieves a signing key from the database using the provided UUID.
         *
         * @param string $uuid The UUID of the signing key to retrieve.
         * @return SigningKeyRecord|null The signing key record if found, or null if no record exists.
         * @throws DatabaseOperationException If a database error occurs during the operation.
         */
        public static function getSigningKey(string $uuid): ?SigningKeyRecord
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT * FROM signing_keys WHERE uuid=:uuid");
                $statement->bindParam(':uuid', $uuid);
                $statement->execute();

                if($row = $statement->fetch())
                {
                    return SigningKeyRecord::fromArray($row);
                }
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get the signing key from the database', $e);
            }

            return null;
        }

        /**
         * Retrieves the signing keys associated with a specific peer UUID.
         *
         * @param string $peerUuid The UUID of the peer whose signing keys are to be retrieved.
         * @return SigningKeyRecord[] An array of SigningKeyRecord objects representing the signing keys.
         * @throws DatabaseOperationException If an error occurs during the database operation.
         */
        public static function getSigningKeys(string $peerUuid): array
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT * FROM signing_keys WHERE peer_uuid=:peer_uuid");
                $statement->bindParam(':peer_uuid', $peerUuid);
                $statement->execute();

                $signingKeys = [];
                while($row = $statement->fetch())
                {
                    $signingKeys[] = SigningKeyRecord::fromArray($row);
                }

                return $signingKeys;
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get the signing keys from the database', $e);
            }
        }

        /**
         * Verifies the digital signature of a message using the signing key associated with a specific UUID.
         *
         * @param string $message The message whose signature needs to be verified.
         * @param string $signature The digital signature to be verified.
         * @param string $uuid The UUID used to retrieve the corresponding signing key.
         * @return bool True if the signature is valid, false otherwise.
         * @throws CryptographyException If an error occurs during the cryptographic operation.
         * @throws DatabaseOperationException If an error occurs during the database operation.
         */
        public static function verifySignature(string $message, string $signature, string $uuid): bool
        {
            $signingKey = self::getSigningKey($uuid);
            if($signingKey === null)
            {
                return false;
            }

            return Cryptography::verifyMessage($message, $signature, $signingKey->getPublicKey());
        }
    }