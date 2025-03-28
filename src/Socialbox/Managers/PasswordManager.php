<?php

    namespace Socialbox\Managers;
    
    use DateTime;
    use InvalidArgumentException;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Validator;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\PeerDatabaseRecord;

    class PasswordManager
    {
        /**
         * Checks if the given peer UUID is associated with a password in the database.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer, or an instance of RegisteredPeerRecord from which the UUID will be retrieved.
         * @return bool Returns true if the peer UUID is associated with a password, otherwise false.
         * @throws DatabaseOperationException If an error occurs while querying the database.
         */
        public static function usesPassword(string|PeerDatabaseRecord $peerUuid): bool
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }
            
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM authentication_passwords WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();

                return $stmt->fetchColumn() > 0;
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while checking the password usage in the database', $e);
            }
        }

        /**
         * Sets a secured password for the given peer UUID or registered peer record.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier or registered peer record of the user.
         * @param string $hash The plaintext password to be securely stored.
         * @return void
         * @throws DatabaseOperationException If an error occurs while storing the password in the database.
         * @throws CryptographyException If an error occurs during password encryption or hashing.
         */
        public static function setPassword(string|PeerDatabaseRecord $peerUuid, string $hash): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            // Throws an exception if the hash is invalid
            if(!Cryptography::validatePasswordHash($hash))
            {
                throw new CryptographyException('Invalid password aragon2id hash');
            }

            $encryptionKey = Configuration::getCryptographyConfiguration()->getRandomInternalEncryptionKey();
            $securedPassword = Cryptography::encryptMessage($hash, $encryptionKey, Configuration::getCryptographyConfiguration()->getEncryptionKeysAlgorithm());

            try
            {
                $stmt = Database::getConnection()->prepare("INSERT INTO authentication_passwords (peer_uuid, hash) VALUES (:peer_uuid, :hash)");
                $stmt->bindParam(":peer_uuid", $peerUuid);
                $stmt->bindParam(':hash', $securedPassword);

                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to set password for user %s', $peerUuid), $e);
            }
        }

        /**
         * Updates the secured password associated with the given peer UUID.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier or registered peer record of the user.
         * @param string $hash The new password to be stored securely.
         * @return void
         * @throws DatabaseOperationException If an error occurs while updating the password in the database.
         * @throws CryptographyException If an error occurs while encrypting the password or validating the hash.
         */
        public static function updatePassword(string|PeerDatabaseRecord $peerUuid, string $hash): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new CryptographyException('The given internal peer UUID is not a valid UUID V4');
            }

            if(!Cryptography::validatePasswordHash($hash))
            {
                throw new CryptographyException('Invalid password argon2id hash');
            }

            $encryptionKey = Configuration::getCryptographyConfiguration()->getRandomInternalEncryptionKey();
            $securedPassword = Cryptography::encryptMessage($hash, $encryptionKey, Configuration::getCryptographyConfiguration()->getEncryptionKeysAlgorithm());

            try
            {
                $stmt = Database::getConnection()->prepare("UPDATE authentication_passwords SET hash=:hash, updated=:updated WHERE peer_uuid=:peer_uuid");
                $updated = (new DateTime())->setTimestamp(time())->format('Y-m-d H:i:s');
                $stmt->bindParam(':hash', $securedPassword);
                $stmt->bindParam(':updated', $updated);
                $stmt->bindParam(':peer_uuid', $peerUuid);

                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to update password for user %s', $peerUuid), $e);
            }
        }

        /**
         * Deletes the stored password for a specific peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier of the peer, or an instance of RegisteredPeerRecord.
         * @return void
         * @throws DatabaseOperationException If an error occurs during the database operation.
         */
        public static function deletePassword(string|PeerDatabaseRecord $peerUuid): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('DELETE FROM authentication_passwords WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while deleting the password', $e);
            }
        }

        /**
         * Verifies a given password against a stored password hash for a specific peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier of the peer, or an instance of RegisteredPeerRecord.
         * @param string $sha512 The SHA-512 hash of the password to be verified.
         * @return bool Returns true if the password matches the stored hash; false otherwise.
         * @throws CryptographyException If the password hash is invalid or an error occurs during the cryptographic operation.
         * @throws DatabaseOperationException If an error occurs during the database operation.
         */
        public static function verifyPassword(string|PeerDatabaseRecord $peerUuid, string $sha512): bool
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new CryptographyException('The given internal peer UUID is not a valid UUID V4');
            }

            if(!Cryptography::validateSha512($sha512))
            {
                throw new CryptographyException('Invalid SHA-512 hash');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT hash FROM authentication_passwords WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();

                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                if($record === false)
                {
                    throw new DatabaseOperationException('No password hash found for the given peer UUID');
                }

                $encryptedHash = $record['hash'];
                $decryptedHash = null;
                foreach(Configuration::getCryptographyConfiguration()->getInternalEncryptionKeys() as $key)
                {
                    try
                    {
                        $decryptedHash = Cryptography::decryptMessage($encryptedHash, $key, Configuration::getCryptographyConfiguration()->getEncryptionKeysAlgorithm());
                    }
                    catch(CryptographyException)
                    {
                        continue;
                    }
                }

                if($decryptedHash === null)
                {
                    throw new CryptographyException('Cannot decrypt hashed password');
                }

                return Cryptography::verifyPassword($sha512, $decryptedHash);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while verifying the password', $e);
            }
        }
    }