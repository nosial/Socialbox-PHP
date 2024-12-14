<?php

    namespace Socialbox\Managers;
    
    use PDO;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\SecuredPassword;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\RegisteredPeerRecord;
    use Socialbox\Objects\Database\SecurePasswordRecord;

    class PasswordManager
    {
        /**
         * Checks if the given peer UUID is associated with a password in the database.
         *
         * @param string|RegisteredPeerRecord $peerUuid The UUID of the peer, or an instance of RegisteredPeerRecord from which the UUID will be retrieved.
         * @return bool Returns true if the peer UUID is associated with a password, otherwise false.
         * @throws DatabaseOperationException If an error occurs while querying the database.
         */
        public static function usesPassword(string|RegisteredPeerRecord $peerUuid): bool
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM authentication_passwords WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();

                return $stmt->fetchColumn() > 0;
            }
            catch (\PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while checking the password usage in the database', $e);
            }
        }

        /**
         * Sets a password for a given user or peer record by securely encrypting it
         * and storing it in the authentication_passwords database table.
         *
         * @param string|RegisteredPeerRecord $peerUuid The UUID of the peer or an instance of RegisteredPeerRecord.
         * @param string $password The plaintext password to be securely stored.
         * @throws CryptographyException If an error occurs while securing the password.
         * @throws DatabaseOperationException If an error occurs while attempting to store the password in the database.
         * @throws \DateMalformedStringException If the updated timestamp cannot be formatted.
         * @return void
         */
        public static function setPassword(string|RegisteredPeerRecord $peerUuid, string $password): void
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            
            $encryptionRecord = EncryptionRecordsManager::getRandomRecord();
            $securedPassword = SecuredPassword::securePassword($peerUuid, $password, $encryptionRecord);

            try
            {
                $stmt = Database::getConnection()->prepare("INSERT INTO authentication_passwords (peer_uuid, iv, encrypted_password, encrypted_tag) VALUES (:peer_uuid, :iv, :encrypted_password, :encrypted_tag)");
                $stmt->bindParam(":peer_uuid", $peerUuid);

                $iv = $securedPassword->getIv();
                $stmt->bindParam(':iv', $iv);

                $encryptedPassword = $securedPassword->getEncryptedPassword();
                $stmt->bindParam(':encrypted_password', $encryptedPassword);

                $encryptedTag = $securedPassword->getEncryptedTag();
                $stmt->bindParam(':encrypted_tag', $encryptedTag);

                $stmt->execute();
            }
            catch(\PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to set password for user %s', $peerUuid), $e);
            }
        }

        /**
         * Updates the password for a given peer identified by their UUID or a RegisteredPeerRecord.
         *
         * @param string|RegisteredPeerRecord $peerUuid The UUID of the peer or an instance of RegisteredPeerRecord.
         * @param string $newPassword The new password to be set for the peer.
         * @throws CryptographyException If an error occurs while securing the new password.
         * @throws DatabaseOperationException If the update operation fails due to a database error.
         * @throws \DateMalformedStringException If the updated timestamp cannot be formatted.
         * @returns void
         */
        public static function updatePassword(string|RegisteredPeerRecord $peerUuid, string $newPassword): void
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }


            $encryptionRecord = EncryptionRecordsManager::getRandomRecord();
            $securedPassword = SecuredPassword::securePassword($peerUuid, $newPassword, $encryptionRecord);

            try
            {
                $stmt = Database::getConnection()->prepare("UPDATE authentication_passwords SET iv=:iv, encrypted_password=:encrypted_password, encrypted_tag=:encrypted_tag, updated=:updated WHERE peer_uuid=:peer_uuid");
                $stmt->bindParam(":peer_uuid", $peerUuid);

                $iv = $securedPassword->getIv();
                $stmt->bindParam(':iv', $iv);

                $encryptedPassword = $securedPassword->getEncryptedPassword();
                $stmt->bindParam(':encrypted_password', $encryptedPassword);

                $encryptedTag = $securedPassword->getEncryptedTag();
                $stmt->bindParam(':encrypted_tag', $encryptedTag);

                $updated = $securedPassword->getUpdated()->format('Y-m-d H:i:s');
                $stmt->bindParam(':updated', $updated);

                $stmt->execute();
            }
            catch(\PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to update password for user %s', $peerUuid), $e);
            }
        }

        /**
         * Retrieves the password record associated with the given peer UUID.
         *
         * @param string|RegisteredPeerRecord $peerUuid The UUID of the peer or an instance of RegisteredPeerRecord.
         * @return SecurePasswordRecord|null Returns a SecurePasswordRecord if found, or null if no record is present.
         * @throws DatabaseOperationException If a database operation error occurs during the retrieval process.
         */
        private static function getPassword(string|RegisteredPeerRecord $peerUuid): ?SecurePasswordRecord
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $statement = Database::getConnection()->prepare("SELECT * FROM authentication_passwords WHERE peer_uuid=:peer_uuid LIMIT 1");
                $statement->bindParam(':peer_uuid', $peerUuid);

                $statement->execute();
                $data = $statement->fetch(PDO::FETCH_ASSOC);

                if ($data === false)
                {
                    return null;
                }

                return SecurePasswordRecord::fromArray($data);
            }
            catch(\PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to retrieve password record for user %s', $peerUuid), $e);
            }
        }

        /**
         * Verifies if the provided password matches the secured password associated with the given peer UUID.
         *
         * @param string|RegisteredPeerRecord $peerUuid The unique identifier or registered peer record of the user.
         * @param string $password The password to be verified.
         * @return bool Returns true if the password is verified successfully; otherwise, false.
         * @throws DatabaseOperationException If an error occurs while retrieving the password record from the database.
         * @throws CryptographyException If an error occurs while verifying the password.
         */
        public static function verifyPassword(string|RegisteredPeerRecord $peerUuid, string $password): bool
        {
            $securedPassword = self::getPassword($peerUuid);
            if($securedPassword === null)
            {
                return false;
            }

            $encryptionRecords = EncryptionRecordsManager::getAllRecords();
            return SecuredPassword::verifyPassword($password, $securedPassword, $encryptionRecords);
        }
    }