<?php

    namespace Socialbox\Managers;

    use ncc\ThirdParty\Symfony\Uid\UuidV4;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Database;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\ContactDatabaseRecord;
    use Socialbox\Objects\PeerAddress;

    class ContactManager
    {
        /**
         * Determines if a given contact address is associated with a specified peer UUID in the database.
         *
         * @param string $peerUuid The unique identifier of the peer.
         * @param string|PeerAddress $contactAddress The contact's address, either as a string or a PeerAddress instance.
         * @return bool Returns true if the contact exists in the database; otherwise, returns false.
         */
        public static function isContact(string $peerUuid, string|PeerAddress $contactAddress): bool
        {
            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }

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
         * @param string $peerUuid The unique identifier of the peer.
         * @param string|PeerAddress $contactAddress The contact's address, either as a string or a PeerAddress instance.
         * @param ContactRelationshipType $relationship The type of relationship between the peer and the contact. Defaults to ContactRelationshipType::MUTUAL.
         * @return string The UUID of the newly created contact.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function createContact(string $peerUuid, string|PeerAddress $contactAddress, ContactRelationshipType $relationship=ContactRelationshipType::MUTUAL): string
        {
            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }

            $uuid = UuidV4::v4()->toRfc4122();

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
         * @param string $peerUuid The unique identifier for the peer whose contact count is to be retrieved.
         * @return int The total number of contacts for the given peer.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContactCount(string $peerUuid): int
        {
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
         * @param string $peerUuid The unique identifier for the peer whose contact is to be retrieved.
         * @param string|PeerAddress $contactAddress The address of the contact, either as a string or a PeerAddress object.
         * @return ContactDatabaseRecord|null The retrieved ContactRecord instance if found, or null if no matching contact exists.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContact(string $peerUuid, string|PeerAddress $contactAddress): ?ContactDatabaseRecord
        {
            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }

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
         * @param string $peerUuid The unique identifier for the peer whose contact is to be deleted.
         * @param string|PeerAddress $contactAddress The address of the contact to be deleted. Can be provided as a string or a PeerAddress instance.
         * @return void
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function deleteContact(string $peerUuid, string|PeerAddress $contactAddress): void
        {
            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
            }

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
         * Updates the relationship type of a contact associated with a specific peer.
         *
         * @param string $peerUuid The unique identifier for the peer whose contact relationship is to be updated.
         * @param string|PeerAddress $contactAddress The address of the contact to update. Can be provided as a string or an instance of PeerAddress.
         * @param ContactRelationshipType $relationship The new relationship type to assign to the contact.
         * @return void
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function updateContactRelationship(string $peerUuid, string|PeerAddress $contactAddress, ContactRelationshipType $relationship): void
        {
            if($contactAddress instanceof PeerAddress)
            {
                $contactAddress = $contactAddress->getAddress();
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
         * @param string $uuid The unique identifier of the contact to retrieve.
         * @return ContactDatabaseRecord|null A ContactRecord instance if the contact is found, or null if no contact exists with the provided UUID.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContactByUuid(string $uuid): ?ContactDatabaseRecord
        {
            try
            {
                // Get the contact from the database
                $statement = Database::getConnection()->prepare('SELECT * FROM contacts WHERE uuid=:uuid LIMIT 1');
                $statement->bindParam(':uuid', $uuid);
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
         * @param string $peerUuid The unique identifier for the peer whose contacts are to be retrieved.
         * @param int $limit The maximum number of contacts to retrieve per page. Defaults to 100.
         * @param int $page The page number to retrieve. Defaults to 1.
         * @return ContactDatabaseRecord[] An array of ContactRecord instances representing the contacts for the given peer.
         * @throws DatabaseOperationException If the database query fails.
         */
        public static function getContacts(string $peerUuid, int $limit=100, int $page=1): array
        {
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
    }