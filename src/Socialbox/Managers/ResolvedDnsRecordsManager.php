<?php

    namespace Socialbox\Managers;

    use DateTime;
    use Exception;
    use PDOException;
    use Socialbox\Classes\Database;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\DnsRecord;

    class ResolvedDnsRecordsManager
    {
        /**
         * Checks whether a resolved server record exists in the database for the provided domain.
         *
         * @param string $domain The domain name to check for existence in the resolved records.
         * @return bool True if the resolved server record exists, otherwise false.
         * @throws DatabaseOperationException If the process encounters a database error.
         */
        public static function resolvedServerExists(string $domain): bool
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT COUNT(*) FROM resolved_dns_records WHERE domain=?");
                $statement->bindParam(1, $domain);
                $statement->execute();
                return $statement->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if a resolved server exists in the database', $e);
            }
        }

        /**
         * Deletes a resolved server record from the database for the provided domain.
         *
         * @param string $domain The domain name of the resolved server to be deleted.
         * @return void
         * @throws DatabaseOperationException If the deletion process encounters a database error.
         */
        public static function deleteResolvedServer(string $domain): void
        {
            try
            {
                $statement = Database::getConnection()->prepare("DELETE FROM resolved_dns_records WHERE domain=?");
                $statement->bindParam(1, $domain);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete a resolved server from the database', $e);
            }
        }

        /**
         * Retrieves the last updated timestamp of a resolved server from the database for a given domain.
         *
         * This method queries the database to fetch the timestamp indicating when the resolved server
         * associated with the specified domain was last updated.
         *
         * @param string $domain The domain name for which the last updated timestamp is to be retrieved.
         * @return DateTime The DateTime object representing the last updated timestamp of the resolved server.
         *
         * @throws DatabaseOperationException If the operation to retrieve the updated timestamp from the
         *                                     database fails.
         */
        public static function getResolvedServerUpdated(string $domain): DateTime
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT updated FROM resolved_dns_records WHERE domain=?");
                $statement->bindParam(1, $domain);
                $statement->execute();
                $result = $statement->fetchColumn();
                return new DateTime($result);
            }
            catch(Exception $e)
            {
                throw new DatabaseOperationException('Failed to get the updated date of a resolved server from the database', $e);
            }
        }

        /**
         * Retrieves a DNS record for the specified domain from the database.
         *
         * This method fetches the DNS record details, such as the RPC endpoint, public key,
         * and expiration details, associated with the provided domain. If no record is found,
         * it returns null.
         *
         * @param string $domain The domain name for which the DNS record is to be retrieved.
         * @return DnsRecord|null The DNS record object if found, or null if no record exists for the given domain.
         *
         * @throws DatabaseOperationException If the operation to retrieve the DNS record from
         *                                     the database fails.
         */
        public static function getDnsRecord(string $domain): ?DnsRecord
        {
            try
            {
                $statement = Database::getConnection()->prepare("SELECT * FROM resolved_dns_records WHERE domain=?");
                $statement->bindParam(1, $domain);
                $statement->execute();
                $result = $statement->fetch();

                if($result === false)
                {
                    return null;
                }

                return DnsRecord::fromArray([
                    'rpc_endpoint' => $result['rpc_endpoint'],
                    'public_key' => $result['public_key'],
                    'expires' => $result['expires']
                ]);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get a resolved server from the database', $e);
            }
        }

        /**
         * Adds or updates a resolved server in the database based on the provided domain and DNS record.
         *
         * If a resolved server for the given domain already exists in the database, the server's details
         * will be updated. Otherwise, a new record will be inserted into the database.
         *
         * @param string $domain The domain name associated with the resolved server.
         * @param DnsRecord $dnsRecord An object containing DNS record details such as the RPC endpoint,
         *                             public key, and expiration details.
         * @return void
         * @throws DatabaseOperationException If the operation to add or update the resolved server in
         *                                     the database fails.
         */
        public static function addResolvedServer(string $domain, DnsRecord $dnsRecord): void
        {
            $endpoint = $dnsRecord->getRpcEndpoint();
            $publicKey = $dnsRecord->getPublicSigningKey();

            if($domain === null || $endpoint === null || $publicKey === null)
            {
                throw new DatabaseOperationException('Failed to add a resolved server to the database: Invalid parameters');
            }

            if(self::resolvedServerExists($domain))
            {
                try
                {
                    $statement = Database::getConnection()->prepare("UPDATE resolved_dns_records SET rpc_endpoint=:rpc_endpoint, public_key=:public_key, expires=:expires, updated=:updated WHERE domain=:domain");
                    $statement->bindParam(':rpc_endpoint', $endpoint);
                    $statement->bindParam(':public_key', $publicKey);
                    $expires = (new DateTime())->setTimestamp($dnsRecord->getExpires())->format('Y-m-d H:i:s');
                    $statement->bindParam(':expires', $expires);
                    $updated = (new DateTime())->format('Y-m-d H:i:s');
                    $statement->bindParam(':updated', $updated);
                    $statement->bindParam(':domain', $domain);
                    $statement->execute();
                }
                catch(PDOException $e)
                {
                    throw new DatabaseOperationException('Failed to update a resolved server in the database', $e);
                }

                return;
            }

            try
            {
                $statement = Database::getConnection()->prepare("INSERT INTO resolved_dns_records (domain, rpc_endpoint, public_key, expires) VALUES (:domain, :rpc_endpoint, :public_key, :expires)");
                $statement->bindParam(':domain', $domain);
                $statement->bindParam(':rpc_endpoint', $endpoint);
                $statement->bindParam(':public_key', $publicKey);
                $expires = (new DateTime())->setTimestamp($dnsRecord->getExpires())->format('Y-m-d H:i:s');
                $statement->bindParam(':expires', $expires);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to add a resolved server to the database', $e);
            }
        }
    }