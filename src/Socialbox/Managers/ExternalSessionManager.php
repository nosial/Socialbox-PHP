<?php

    namespace Socialbox\Managers;

    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Database;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Client\ExportedSession;

    class ExternalSessionManager
    {
        /**
         * Checks if a session exists in the database for the specified domain.
         *
         * @param string $domain The domain to check for an existing session in the external_sessions table.
         * @return bool Returns true if a session exists for the specified domain, otherwise false.
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function sessionExists(string $domain): bool
        {
            $domain = strtolower($domain);

            try
            {
                $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM external_sessions WHERE domain=:domain LIMIT 1");
                $stmt->bindParam(':domain', $domain);
                $stmt->execute();

                return $stmt->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if a session exists in the database', $e);
            }
        }

        /**
         * Adds a new external session to the database.
         *
         * @param ExportedSession $exportedSession The session data to be added, containing all necessary attributes
         *                                         such as server keys, client keys, and other metadata.
         * @return void
         * @throws DatabaseOperationException If the database operation fails.
         */
        public static function addSession(ExportedSession $exportedSession): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare("INSERT INTO external_sessions (domain, rpc_endpoint, session_uuid, transport_encryption_algorithm, server_keypair_expires, server_public_signing_key, server_public_encryption_key, host_public_encryption_key, host_private_encryption_key, private_shared_secret, host_transport_encryption_key, server_transport_encryption_key) VALUES (:domain, :rpc_endpoint, :session_uuid, :transport_encryption_algorithm, :server_keypair_expires, :server_public_signing_key, :server_public_encryption_key, :host_public_encryption_key, :host_private_encryption_key, :private_shared_secret, :host_transport_encryption_key, :server_transport_encryption_key)");
                $domain = strtolower($exportedSession->getRemoteServer());
                $stmt->bindParam(':domain', $domain);
                $rpcEndpoint = $exportedSession->getRpcEndpoint();
                $stmt->bindParam(':rpc_endpoint', $rpcEndpoint);
                $sessionUuid = $exportedSession->getSessionUuid();
                $stmt->bindParam(':session_uuid', $sessionUuid);
                $transportEncryptionAlgorithm = $exportedSession->getTransportEncryptionAlgorithm();
                $stmt->bindParam(':transport_encryption_algorithm', $transportEncryptionAlgorithm);
                $serverKeypairExpires = $exportedSession->getServerKeypairExpires();
                $stmt->bindParam(':server_keypair_expires', $serverKeypairExpires);
                $serverPublicSigningKey = $exportedSession->getServerPublicSigningKey();
                $stmt->bindParam(':server_public_signing_key', $serverPublicSigningKey);
                $serverPublicEncryptionKey = $exportedSession->getServerPublicEncryptionKey();
                $stmt->bindParam(':server_public_encryption_key', $serverPublicEncryptionKey);
                $hostPublicEncryptionKey = $exportedSession->getClientPublicEncryptionKey();
                $stmt->bindParam(':host_public_encryption_key', $hostPublicEncryptionKey);
                $hostPrivateEncryptionKey = $exportedSession->getClientPrivateEncryptionKey();
                $stmt->bindParam(':host_private_encryption_key', $hostPrivateEncryptionKey);
                $privateSharedSecret = $exportedSession->getPrivateSharedSecret();
                $stmt->bindParam(':private_shared_secret', $privateSharedSecret);
                $hostTransportEncryptionKey = $exportedSession->getClientTransportEncryptionKey();
                $stmt->bindParam(':host_transport_encryption_key', $hostTransportEncryptionKey);
                $serverTransportEncryptionKey = $exportedSession->getServerTransportEncryptionKey();
                $stmt->bindParam(':server_transport_encryption_key', $serverTransportEncryptionKey);

                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to add a session to the database', $e);
            }
        }

        /**
         * Retrieves a session associated with the specified domain from the database.
         *
         * @param string $domain The domain for which the session should be retrieved.
         * @return ExportedSession|null The retrieved session as an ExportedSession object, or null if no session is found.
         * @throws DatabaseOperationException If the operation fails due to a database error.
         */
        public static function getSession(string $domain): ?ExportedSession
        {
            $domain = strtolower($domain);

            try
            {
                $stmt = Database::getConnection()->prepare("SELECT * FROM external_sessions WHERE domain=:domain LIMIT 1");
                $stmt->bindParam(':domain', $domain);
                $stmt->execute();
                $result = $stmt->fetch();

                if($result === false)
                {
                    return null;
                }
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve the session from the database', $e);
            }

            return ExportedSession::fromArray([
                'peer_address' => sprintf('%s@%s', ReservedUsernames::HOST->value, Configuration::getInstanceConfiguration()->getDomain()),
                'rpc_endpoint' => $result['rpc_endpoint'],
                'remote_server' => $result['domain'],
                'session_uuid' => $result['session_uuid'],
                'transport_encryption_algorithm' => $result['transport_encryption_algorithm'],
                'server_keypair_expires' => $result['server_keypair_expires'],
                'server_public_signing_key' => $result['server_public_signing_key'],
                'server_public_encryption_key' => $result['server_public_encryption_key'],
                'client_public_signing_key' => Configuration::getCryptographyConfiguration()->getHostPublicKey(),
                'client_private_signing_key' => Configuration::getCryptographyConfiguration()->getHostPrivateKey(),
                'client_public_encryption_key' => $result['host_public_encryption_key'],
                'client_private_encryption_key' => $result['host_private_encryption_key'],
                'private_shared_secret' => $result['private_shared_secret'],
                'client_transport_encryption_key' => $result['host_transport_encryption_key'],
                'server_transport_encryption_key' => $result['server_transport_encryption_key']
            ]);
        }

        /**
         * Removes a session associated with the specified domain from the database.
         *
         * @param string $domain The domain for which the session should be removed.
         * @return void
         * @throws DatabaseOperationException If the operation fails due to a database error.
         */
        public static function removeSession(string $domain): void
        {
            $domain = strtolower($domain);

            try
            {
                $stmt = Database::getConnection()->prepare("DELETE FROM external_sessions WHERE domain=:domain");
                $stmt->bindParam(':domain', $domain);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to remove a session from the database', $e);
            }
        }


        /**
         * Updates the last accessed timestamp for a specific external session in the database.
         *
         * @param string $domain The domain associated with the external session to update.
         * @return void
         * @throws DatabaseOperationException If the update operation fails.
         */
        public static function updateLastAccessed(string $domain): void
        {


            try
            {
                $stmt = Database::getConnection()->prepare("UPDATE external_sessions SET last_accessed=CURRENT_TIMESTAMP WHERE domain=:domain");
                $stmt->bindParam(':domain', $domain);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the last accessed time of a session in the database', $e);
            }
        }
    }