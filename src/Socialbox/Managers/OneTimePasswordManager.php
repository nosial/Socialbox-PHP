<?php

    namespace Socialbox\Managers;

    use DateTime;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\OtpCryptography;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\RegisteredPeerRecord;

    class OneTimePasswordManager
    {
        /**
         * Checks if a given peer uses OTP for authentication.
         *
         * @param string|RegisteredPeerRecord $peerUuid Either a UUID as a string or a RegisteredPeerRecord object representing the peer.
         * @return bool Returns true if the peer uses OTP, otherwise false.
         * @throws DatabaseOperationException Thrown when a database error occurs.
         */
        public static function usesOtp(string|RegisteredPeerRecord $peerUuid): bool
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM authentication_otp WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while checking the OTP usage in the database', $e);
            }

            return $stmt->fetchColumn() > 0;
        }

        /**
         * Creates and stores a new OTP (One-Time Password) secret for the specified peer, and generates a key URI.
         *
         * @param string|RegisteredPeerRecord $peer The unique identifier of the peer, either as a string UUID
         *                                          or an instance of RegisteredPeerRecord.
         * @return string The generated OTP key URI that can be used for applications like authenticator apps.
         * @throws DatabaseOperationException If there is an error during the database operation.
         */
        public static function createOtp(string|RegisteredPeerRecord $peer): string
        {
            if(is_string($peer))
            {
                $peer = RegisteredPeerManager::getPeer($peer);
            }

            $secret = OtpCryptography::generateSecretKey(Configuration::getSecurityConfiguration()->getOtpSecretKeyLength());
            $encryptionKey = Configuration::getCryptographyConfiguration()->getRandomInternalEncryptionKey();
            $encryptedSecret = Cryptography::encryptMessage($secret, $encryptionKey, Configuration::getCryptographyConfiguration()->getEncryptionKeysAlgorithm());

            try
            {
                $stmt = Database::getConnection()->prepare("INSERT INTO authentication_otp (peer_uuid, secret) VALUES (:peer_uuid, :secret)");
                $stmt->bindParam(':peer_uuid', $peer);
                $stmt->bindParam(':secret', $encryptedSecret);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while creating the OTP secret in the database', $e);
            }

            return OtpCryptography::generateKeyUri($peer->getAddress(), $secret,
                Configuration::getInstanceConfiguration()->getDomain(),
                Configuration::getSecurityConfiguration()->getOtpTimeStep(),
                Configuration::getSecurityConfiguration()->getOtpDigits(),
                Configuration::getSecurityConfiguration()->getOtpHashAlgorithm()
            );
        }

        /**
         * Verifies the provided OTP (One-Time Password) against the stored secret associated with the specified peer.
         *
         * @param string|RegisteredPeerRecord $peerUuid The unique identifier of the peer, either as a string UUID
         *                                              or an instance of RegisteredPeerRecord.
         * @param string $otp The OTP to be verified.
         * @return bool Returns true if the OTP is valid; otherwise, false.
         * @throws DatabaseOperationException If there is an error during the database operation.
         * @throws CryptographyException If there is a failure in decrypting the stored OTP secret.
         */
        public static function verifyOtp(string|RegisteredPeerRecord $peerUuid, string $otp): bool
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT secret FROM authentication_otp WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();

                $encryptedSecret = $stmt->fetchColumn();

                if($encryptedSecret === false)
                {
                    return false;
                }
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while retrieving the OTP secret from the database', $e);
            }

            $decryptedSecret = null;
            foreach(Configuration::getCryptographyConfiguration()->getInternalEncryptionKeys() as $encryptionKey)
            {
                try
                {
                    $decryptedSecret = Cryptography::decryptMessage($encryptedSecret, $encryptionKey, Configuration::getCryptographyConfiguration()->getEncryptionKeysAlgorithm());
                }
                catch(CryptographyException)
                {
                    continue;
                }
            }

            if($decryptedSecret === null)
            {
                throw new CryptographyException('Failed to decrypt the OTP secret');
            }

            return OtpCryptography::verifyOTP($decryptedSecret, $otp,
                Configuration::getSecurityConfiguration()->getOtpTimeStep(),
                Configuration::getSecurityConfiguration()->getOtpWindow(),
                Configuration::getSecurityConfiguration()->getOtpDigits(),
                Configuration::getSecurityConfiguration()->getOtpHashAlgorithm()
            );
        }

        /**
         * Deletes the OTP record associated with the specified peer.
         *
         * @param string|RegisteredPeerRecord $peerUuid The peer's UUID or an instance of RegisteredPeerRecord whose OTP record needs to be deleted.
         * @return void
         * @throws DatabaseOperationException if the database operation fails.
         */
        public static function deleteOtp(string|RegisteredPeerRecord $peerUuid): void
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('DELETE FROM authentication_otp WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while deleting the OTP secret from the database', $e);
            }
        }

        /**
         * Retrieves the last updated timestamp for the OTP record of the specified peer.
         *
         * @param string|RegisteredPeerRecord $peerUuid The peer's UUID or an instance of RegisteredPeerRecord whose OTP record's last updated timestamp needs to be retrieved
         * @return int The last updated timestamp of the OTP record, or 0 if no such record exists
         */
        public static function getLastUpdated(string|RegisteredPeerRecord $peerUuid): int
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT updated FROM authentication_otp WHERE peer_uuid=:uuid');
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();

                /** @var \DateTime $updated */
                $updated = $stmt->fetchColumn();

                if($updated === false)
                {
                    return 0;
                }
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('An error occurred while retrieving the last updated timestamp from the database', $e);
            }

            return $updated->getTimestamp();
        }

        /**
         * Updates the last updated timestamp for the OTP record of the specified peer.
         *
         * @param string|RegisteredPeerRecord $peerUuid The peer's UUID or an instance of RegisteredPeerRecord whose OTP record needs to be updated.
         * @return void
         * @throws DatabaseOperationException if the database operation fails.
         */
        public static function updateOtp(string|RegisteredPeerRecord $peerUuid): void
        {
            if($peerUuid instanceof RegisteredPeerRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE authentication_otp SET updated=:updated WHERE peer_uuid=:uuid');
                $updated = (new DateTime())->setTimestamp(time());
                $stmt->bindParam(':updated', $updated);
                $stmt->bindParam(':uuid', $peerUuid);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to update the OTP secret for user %s', $peerUuid), $e);
            }
        }
    }