<?php

    namespace Socialbox\Managers;

    use DateMalformedStringException;
    use DateTime;
    use InvalidArgumentException;
    use LogLib\Log;
    use PDO;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Logger;
    use Socialbox\Enums\SessionState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Objects\Database\RegisteredPeerRecord;
    use Socialbox\Objects\Database\SessionRecord;
    use Symfony\Component\Uid\Uuid;

    class SessionManager
    {
        /**
         * Creates a new session with the given public key.
         *
         * @param string $publicKey The public key to associate with the new session.
         *
         * @return string The UUID of the newly created session.
         *
         * @throws InvalidArgumentException If the public key is empty or invalid.
         * @throws DatabaseOperationException If there is an error while creating the session in the database.
         */
        public static function createSession(string $publicKey): string
        {
            if($publicKey === '')
            {
                throw new InvalidArgumentException('The public key cannot be empty', 400);
            }

            if(!Cryptography::validatePublicKey($publicKey))
            {
                throw new InvalidArgumentException('The given public key is invalid', 400);
            }

            $uuid = Uuid::v4()->toRfc4122();

            try
            {
                $statement = Database::getConnection()->prepare("INSERT INTO sessions (uuid, public_key) VALUES (?, ?)");
                $statement->bindParam(1, $uuid);
                $statement->bindParam(2, $publicKey);
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
         * @throws StandardException
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
                    throw new StandardException(sprintf("The requested session '%s' does not exist"), StandardError::SESSION_NOT_FOUND);
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
         * Update the authenticated peer associated with the given session UUID.
         *
         * @param string $uuid The UUID of the session to update.
         * @param RegisteredPeerRecord|string $registeredPeerUuid
         * @return void
         * @throws DatabaseOperationException
         */
        public static function updateAuthenticatedPeer(string $uuid, RegisteredPeerRecord|string $registeredPeerUuid): void
        {
            if($registeredPeerUuid instanceof RegisteredPeerRecord)
            {
                $registeredPeerUuid = $registeredPeerUuid->getUuid();
            }

            Logger::getLogger()->verbose(sprintf("Assigning peer %s to session %s", $registeredPeerUuid, $uuid));

            try
            {
                $statement = Database::getConnection()->prepare("UPDATE sessions SET authenticated_peer_uuid=? WHERE uuid=?");
                $statement->bindParam(1, $registeredPeerUuid);
                $statement->bindParam(2, $uuid);
                $statement->execute();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update authenticated peer', $e);
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
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update session state', $e);
            }
        }
    }