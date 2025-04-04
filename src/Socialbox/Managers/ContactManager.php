<?php

    namespace Socialbox\Managers;

    use DateTime;
    use InvalidArgumentException;
    use ncc\ThirdParty\Symfony\Uid\UuidV4;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Logger;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\ContactDatabaseRecord;
    use Socialbox\Objects\Database\ContactKnownKeyRecord;
    use Socialbox\Objects\Database\PeerDatabaseRecord;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\Standard\Contact;
    use Socialbox\Objects\Standard\Signature;

    class ContactManager
    {
        /**
         * Determines if a given contact address is associated with a specified peer UUID in the database.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier of the peer.
         * @param string|PeerAddress $contactAddress The contact's address, either as a string or a PeerAddress instance.
         * @return bool Returns true if the contact exists in the database; otherwise, returns false.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function isContact(string|PeerDatabaseRecord $peerUuid, string|PeerAddress $contactAddress): bool
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }
            elseif(!Validator::validateUuid($contactAddress))
            {
                throw new InvalidArgumentException('The given contact address is invalid');
            }

            if(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given peer internal UUID is not a valid UUID V4');
            }

            Logger::getLogger()->debug(sprintf('Querying if %s is a contact of %s', $contactAddress, $peerUuid));

            try
            {
                // Check if the contact is already in the database
                $statement = Database::getConnection()->prepare('SELECT COUNT(*) FROM contacts WHERE peer_uuid=:peer AND contact_peer_address=:address');
                $statement->bindParam(':peer', $peerUuid);
                $statement->bindParam(':address', $contactAddress);
                $statement->execute();

                return $statement->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if a contact exists in the database', $e);
            }
        }

        /**
         * Creates a new contact associated with the given peer UUID and contact address
         * in the database, with a specified relationship type.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier of the peer.
         * @param string|PeerAddress $contactAddress The contact's address, either as a string or a PeerAddress instance.
         * @param ContactRelationshipType $relationship The type of relationship between the peer and the contact. Defaults to ContactRelationshipType::MUTUAL.
         * @return string The UUID of the newly created contact.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function createContact(string|PeerDatabaseRecord $peerUuid, string|PeerAddress $contactAddress, ContactRelationshipType $relationship=ContactRelationshipType::MUTUAL): string
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }
            elseif(!Validator::validatePeerAddress($contactAddress))
            {
                throw new InvalidArgumentException(sprintf('The given contact address %s is not a valid peer address', $contactAddress));
            }

            if(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            $uuid = UuidV4::v4()->toRfc4122();
            Logger::getLogger()->debug(sprintf('Creating new contact (%s) for %s with a relationship of %s as UUID %s', $contactAddress, $peerUuid, $relationship->value, $uuid));

            try
            {
                // Insert the contact into the database
                $statement = Database::getConnection()->prepare('INSERT INTO contacts (uuid, peer_uuid, contact_peer_address, relationship) VALUES (:uuid, :peer, :address, :relationship)');
                $statement->bindParam(':uuid', $uuid);
                $statement->bindParam(':peer', $peerUuid);
                $statement->bindParam(':address', $contactAddress);
                $relationship = $relationship->value;
                $statement->bindParam(':relationship', $relationship);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to create a new contact in the database', $e);
            }

            return $uuid;
        }

        /**
         * Retrieves the total number of contacts associated with a specific peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier for the peer whose contact count is to be retrieved.
         * @return int The total number of contacts for the given peer.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContactCount(string|PeerDatabaseRecord $peerUuid): int
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            Logger::getLogger()->debug(sprintf('Querying contact count for %s', $peerUuid));

            try
            {
                // Get the contact count from the database
                $statement = Database::getConnection()->prepare('SELECT COUNT(*) FROM contacts WHERE peer_uuid=:peer');
                $statement->bindParam(':peer', $peerUuid);
                $statement->execute();
                return $statement->fetchColumn();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get the contact count from the database', $e);
            }
        }

        /**
         * Retrieves a specific contact associated with a peer based on the contact's address.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier for the peer whose contact is to be retrieved.
         * @param string|PeerAddress $contactAddress The address of the contact, either as a string or a PeerAddress object.
         * @return ContactDatabaseRecord|null The retrieved ContactRecord instance if found, or null if no matching contact exists.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContact(string|PeerDatabaseRecord $peerUuid, string|PeerAddress $contactAddress): ?ContactDatabaseRecord
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }
            elseif(!Validator::validatePeerAddress($contactAddress))
            {
                throw new InvalidArgumentException(sprintf('The given contact address %s is not a valid peer address', $contactAddress));
            }

            if(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            Logger::getLogger()->debug(sprintf('Querying contact %s for %s', $contactAddress, $peerUuid));

            try
            {
                // Get the contact from the database
                $statement = Database::getConnection()->prepare('SELECT * FROM contacts WHERE peer_uuid=:peer AND contact_peer_address=:address LIMIT 1');
                $statement->bindParam(':peer', $peerUuid);
                $statement->bindParam(':address', $contactAddress);
                $statement->execute();
                $result = $statement->fetch();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get a contact from the database', $e);
            }

            if($result === false)
            {
                return null;
            }

            return ContactDatabaseRecord::fromArray($result);
        }

        /**
         * Deletes a specific contact associated with a given peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier for the peer whose contact is to be deleted.
         * @param string|PeerAddress $contactAddress The address of the contact to be deleted. Can be provided as a string or a PeerAddress instance.
         * @return void
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function deleteContact(string|PeerDatabaseRecord $peerUuid, string|PeerAddress $contactAddress): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }
            elseif(!Validator::validatePeerAddress($contactAddress))
            {
                throw new InvalidArgumentException('The given contact address is invalid');
            }

            if(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            Logger::getLogger()->debug(sprintf('Deleting contact %s for %s', $contactAddress, $peerUuid));

            try
            {
                $statement = Database::getConnection()->prepare('DELETE FROM contacts WHERE peer_uuid=:peer AND contact_peer_address=:address');
                $statement->bindParam(':peer', $peerUuid);
                $statement->bindParam(':address', $contactAddress);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to delete a contact from the database', $e);
            }
        }

        /**
         * Updates the relationship type of contact associated with a specific peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier for the peer whose contact relationship is to be updated.
         * @param string|PeerAddress $contactAddress The address of the contact to update. Can be provided as a string or an instance of PeerAddress.
         * @param ContactRelationshipType $relationship The new relationship type to assign to the contact.
         * @return void
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function updateContactRelationship(string|PeerDatabaseRecord $peerUuid, string|PeerAddress $contactAddress, ContactRelationshipType $relationship): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }
            elseif(!Validator::validatePeerAddress($contactAddress))
            {
                throw new InvalidArgumentException(sprintf('The given contact address %s is not a valid peer address', $contactAddress));
            }

            if(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            try
            {
                $statement = Database::getConnection()->prepare('UPDATE contacts SET relationship=:relationship WHERE peer_uuid=:peer AND contact_peer_address=:address');
                $relationship = $relationship->value;
                $statement->bindParam(':relationship', $relationship);
                $statement->bindParam(':peer', $peerUuid);
                $statement->bindParam(':address', $contactAddress);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update the relationship for a contact in the database', $e);
            }
        }

        /**
         * Retrieves a contact by its unique identifier.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to retrieve.
         * @return ContactDatabaseRecord|null A ContactRecord instance if the contact is found, or null if no contact exists with the provided UUID.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContactByUuid(string|ContactDatabaseRecord $contactUuid): ?ContactDatabaseRecord
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }

            try
            {
                // Get the contact from the database
                $statement = Database::getConnection()->prepare('SELECT * FROM contacts WHERE uuid=:uuid LIMIT 1');
                $statement->bindParam(':uuid', $contactUuid);
                $statement->execute();
                $result = $statement->fetch();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get a contact from the database', $e);
            }

            if($result === false)
            {
                return null;
            }

            return ContactDatabaseRecord::fromArray($result);
        }

        /**
         * Retrieves a list of contacts associated with a specific peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier for the peer whose contacts are to be retrieved.
         * @param int $limit The maximum number of contacts to retrieve per page. Defaults to 100.
         * @param int $page The page number to retrieve. Defaults to 1.
         * @return ContactDatabaseRecord[] An array of ContactRecord instances representing the contacts for the given peer.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContacts(string|PeerDatabaseRecord $peerUuid, int $limit=100, int $page=1): array
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if ($page < 1)
            {
                $page = 1;
            }

            if ($limit < 1)
            {
                $limit = 1;
            }

            $contacts = [];

            try
            {
                $statement = Database::getConnection()->prepare("SELECT * FROM contacts WHERE peer_uuid=:peer ORDER BY created DESC LIMIT :limit OFFSET :offset");
                $offset = ($page - 1) * $limit;
                $statement->bindParam(':peer', $peerUuid);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
                $statement->execute();

                // Fetch results
                $results = $statement->fetchAll(PDO::FETCH_ASSOC);

                // Convert results to ContactRecord instances
                foreach ($results as $result)
                {
                    $contacts[] = ContactDatabaseRecord::fromArray($result);
                }
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get contacts from the database', $e);
            }
            return $contacts;
        }

        /**
         * Retrieves a list of standard contacts associated with a specific peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier for the peer whose contacts are to be retrieved.
         * @param int $limit The maximum number of contacts to retrieve per page. Defaults to 100.
         * @param int $page The page number to retrieve. Defaults to 1.
         * @return Contact[] An array of ContactRecord instances representing the contacts for the given peer.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getStandardContacts(string|PeerDatabaseRecord $peerUuid, int $limit=100, int $page=1): array
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if ($page < 1)
            {
                throw new InvalidArgumentException('The page number cannot be less than 1');
            }

            if ($limit < 1)
            {
                throw new InvalidArgumentException('The limit cannot be less than 1');
            }
            elseif($limit > Configuration::getPoliciesConfiguration()->getGetContactsLimit())
            {
                throw new InvalidArgumentException('The limit cannot exceed a value of ' . Configuration::getPoliciesConfiguration()->getGetContactsLimit());
            }

            if(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given peer UUID is not a valid UUID V4');
            }

            $contacts = [];

            try
            {
                $statement = Database::getConnection()->prepare("SELECT contact_peer_address FROM contacts WHERE peer_uuid=:peer ORDER BY created DESC LIMIT :limit OFFSET :offset");
                $offset = ($page - 1) * $limit;
                $statement->bindParam(':peer', $peerUuid);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
                $statement->execute();

                // Fetch results
                $results = $statement->fetchAll(PDO::FETCH_ASSOC);

                // Convert results to ContactRecord instances
                foreach ($results as $result)
                {
                    $contacts[] = self::getStandardContact($peerUuid, $result['contact_peer_address']);
                }
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get contacts from the database', $e);
            }
            return $contacts;
        }

        /**
         * Adds a signing key to a contact in the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to add the signing key to.
         * @param Signature $signingKey The signing key to add to the contact.
         * @return void
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function addContactSigningKey(string|ContactDatabaseRecord $contactUuid, Signature $signingKey): void
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }
            elseif(!Validator::validateUuid($contactUuid))
            {
                throw new InvalidArgumentException('The given contact UUID is not a valid UUID V4');
            }

            try
            {
                $statement = Database::getConnection()->prepare('INSERT INTO contacts_known_keys (contact_uuid, signature_uuid, signature_name, signature_key, expires, created, trusted_on) VALUES (:contact_uuid, :signature_uuid, :signature_name, :signature_key, :expires, :created, :trusted_on)');

                $statement->bindParam(':contact_uuid', $contactUuid);
                $signatureUuid = $signingKey->getUuid();
                $statement->bindParam(':signature_uuid', $signatureUuid);
                $signatureName = $signingKey->getName();
                $statement->bindParam(':signature_name', $signatureName);
                $signatureKey = $signingKey->getPublicKey();
                $statement->bindParam(':signature_key', $signatureKey);
                $expires = $signingKey->getExpires();
                if($expires === 0)
                {
                    $expires = null;
                }
                else
                {
                    $expires = (new DateTime())->setTimestamp($expires)->format('Y-m-d H:i:s');
                }
                $statement->bindParam(':expires', $expires);
                $created = (new DateTime())->setTimestamp($signingKey->getCreated())->format('Y-m-d H:i:s');
                $statement->bindParam(':created', $created);
                $trustedOn = (new DateTime())->format('Y-m-d H:i:s');
                $statement->bindParam(':trusted_on', $trustedOn);

                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to add a signing key to a contact in the database', $e);
            }
        }

        /**
         * Removes a signing key from a contact in the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to remove the signing key from.
         * @param string $signatureUuid The UUID of the signing key to remove.
         * @return void
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function removeContactSigningKey(string|ContactDatabaseRecord $contactUuid, string $signatureUuid): void
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }

            try
            {
                $statement = Database::getConnection()->prepare('DELETE FROM contacts_known_keys WHERE contact_uuid=:contact_uuid AND signature_uuid=:signature_uuid');
                $statement->bindParam(':contact_uuid', $contactUuid);
                $statement->bindParam(':signature_uuid', $signatureUuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to remove a signing key from a contact in the database', $e);
            }
        }

        /**
         * Removes all signing keys for a contact from the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to remove all signing keys from.
         * @return void
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function removeAllContactSigningKeys(string|ContactDatabaseRecord $contactUuid): void
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }

            try
            {
                $statement = Database::getConnection()->prepare('DELETE FROM contacts_known_keys WHERE contact_uuid=:contact_uuid');
                $statement->bindParam(':contact_uuid', $contactUuid);
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to remove all signing keys from a contact in the database', $e);
            }
        }

        /**
         * Determines if a signing key UUID exists for a contact in the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to check.
         * @param string $signatureUuid The UUID of the signing key to check.
         * @return bool Returns true if the signing key UUID exists for the contact; otherwise, returns false.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function contactSigningKeyUuidExists(string|ContactDatabaseRecord $contactUuid, string $signatureUuid): bool
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }
            elseif(!Validator::validateUuid($contactUuid))
            {
                throw new InvalidArgumentException('The given contact UUID is not a valid UUID V4');
            }

            if(!Validator::validateUuid($signatureUuid))
            {
                throw new InvalidArgumentException('The given signature UUID is not a valid UUID V4');
            }

            try
            {
                $statement = Database::getConnection()->prepare('SELECT COUNT(*) FROM contacts_known_keys WHERE contact_uuid=:contact_uuid AND signature_uuid=:signature_uuid');
                $statement->bindParam(':contact_uuid', $contactUuid);
                $statement->bindParam(':signature_uuid', $signatureUuid);
                $statement->execute();
                return $statement->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if a signing key UUID exists for a contact in the database', $e);
            }
        }

        /**
         * Determines if a signing key exists for a contact in the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to check.
         * @param string $signatureKey The public key of the signing key to check.
         * @return bool Returns true if the signing key exists for the contact; otherwise, returns false.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function contactSigningKeyExists(string|ContactDatabaseRecord $contactUuid, string $signatureKey): bool
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }
            elseif(!Validator::validateUuid($contactUuid))
            {
                throw new InvalidArgumentException('The given contact UUID is not a valid UUID V4');
            }

            try
            {
                $statement = Database::getConnection()->prepare('SELECT COUNT(*) FROM contacts_known_keys WHERE contact_uuid=:contact_uuid AND signature_key=:signature_key');
                $statement->bindParam(':contact_uuid', $contactUuid);
                $statement->bindParam(':signature_key', $signatureKey);
                $statement->execute();
                return $statement->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if a signing key exists for a contact in the database', $e);
            }
        }

        /**
         * Retrieves a signing key for a contact from the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to retrieve the signing key for.
         * @param string $signatureUuid The UUID of the signing key to retrieve.
         * @return ContactKnownKeyRecord|null The retrieved ContactKnownKeyRecord instance if found, or null if no matching signing key exists.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function contactGetSigningKey(string|ContactDatabaseRecord $contactUuid, string $signatureUuid): ?ContactKnownKeyRecord
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }

            try
            {
                $statement = Database::getConnection()->prepare('SELECT * FROM contacts_known_keys WHERE contact_uuid=:contact_uuid AND signature_uuid=:signature_uuid LIMIT 1');
                $statement->bindParam(':contact_uuid', $contactUuid);
                $statement->bindParam(':signature_uuid', $signatureUuid);
                $statement->execute();
                $result = $statement->fetch();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get a signing key for a contact from the database', $e);
            }

            if($result === false)
            {
                return null;
            }

            return ContactKnownKeyRecord::fromArray($result);
        }

        /**
         * Retrieves all signing keys for a contact from the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to retrieve the signing keys for.
         * @return ContactKnownKeyRecord[] An array of ContactKnownKeyRecord instances representing the signing keys for the contact.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function contactGetSigningKeys(string|ContactDatabaseRecord $contactUuid): array
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }

            $signingKeys = [];

            try
            {
                $statement = Database::getConnection()->prepare('SELECT * FROM contacts_known_keys WHERE contact_uuid=:contact_uuid');
                $statement->bindParam(':contact_uuid', $contactUuid);
                $statement->execute();
                $results = $statement->fetchAll(PDO::FETCH_ASSOC);

                foreach($results as $result)
                {
                    $signingKeys[] = ContactKnownKeyRecord::fromArray($result);
                }
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get signing keys for a contact from the database', $e);
            }

            return $signingKeys;
        }

        /**
         * Retrieves the number of signing keys for a contact from the database.
         *
         * @param string|ContactDatabaseRecord $contactUuid The unique identifier of the contact to retrieve the signing keys count for.
         * @return int The number of signing keys for the contact.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function contactGetSigningKeysCount(string|ContactDatabaseRecord $contactUuid): int
        {
            if($contactUuid instanceof ContactDatabaseRecord)
            {
                $contactUuid = $contactUuid->getUuid();
            }
            elseif(!Validator::validateUuid($contactUuid))
            {
                throw new InvalidArgumentException('The given contact UUID is not a valid UUID V4');
            }

            try
            {
                $statement = Database::getConnection()->prepare('SELECT COUNT(*) FROM contacts_known_keys WHERE contact_uuid=:contact_uuid');
                $statement->bindParam(':contact_uuid', $contactUuid);
                $statement->execute();
                return $statement->fetchColumn();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get the number of signing keys for a contact from the database', $e);
            }
        }

        /**
         * Returns a standard contact record for a given peer UUID and contact address.
         *
         * @param string|PeerDatabaseRecord $peerUuid The unique identifier of the peer.
         * @param string|PeerAddress $contactAddress The contact's address, either as a string or a PeerAddress instance.
         * @return Contact|null The standard contact record if found, or null if no matching contact exists.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getStandardContact(string|PeerDatabaseRecord $peerUuid, string|PeerAddress $contactAddress): ?Contact
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            $contact = self::getContact($peerUuid, $contactAddress);
            if($contact === null)
            {
                return null;
            }

            return new Contact([
                'address' => $contact->getContactPeerAddress(),
                'relationship' => $contact->getRelationship(),
                'known_keys' => self::contactGetSigningKeys($contact),
                'added_timestamp' => $contact->getCreated()->getTimestamp()
            ]);
        }
    }