<?php

    namespace Socialbox\Managers;

    use DateTime;
    use InvalidArgumentException;
    use PDOException;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Logger;
    use Socialbox\Classes\Utilities;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\Status\CaptchaStatus;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\CaptchaRecord;
    use Socialbox\Objects\Database\PeerDatabaseRecord;

    class CaptchaManager
    {
        /**
         * Creates a new captcha for the given peer UUID.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to create the captcha for.
         * @return string The answer to the captcha.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function createCaptcha(string|PeerDatabaseRecord $peerUuid): string
        {
            // If the peer_uuid is a RegisteredPeerRecord, get the UUID
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            $answer = Utilities::randomString(6, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
            $current_time = (new DateTime())->setTimestamp(time())->format('Y-m-d H:i:s');

            if(!self::captchaExists($peerUuid))
            {
                Logger::getLogger()->debug('Creating a new captcha record for peer ' . $peerUuid);
                $statement = Database::getConnection()->prepare("INSERT INTO captcha_images (peer_uuid, created, answer) VALUES (?, ?, ?)");
                $statement->bindParam(1, $peerUuid);
                $statement->bindParam(2, $current_time);
                $statement->bindParam(3, $answer);

                try
                {
                    $statement->execute();
                }
                catch(PDOException $e)
                {
                    throw new DatabaseOperationException('Failed to create a captcha in the database', $e);
                }

                return $answer;
            }

            Logger::getLogger()->debug('Updating an existing captcha record for peer ' . $peerUuid);
            $statement = Database::getConnection()->prepare("UPDATE captcha_images SET answer=?, status='UNSOLVED', created=? WHERE peer_uuid=?");
            $statement->bindParam(1, $answer);
            $statement->bindParam(2, $current_time);
            $statement->bindParam(3, $peerUuid);

            try
            {
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update a captcha in the database', $e);
            }

            return $answer;
        }

        /**
         * Answers a captcha for the given peer UUID.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to answer the captcha for.
         * @param string $answer The answer to the captcha.
         * @return bool True if the answer is correct, false otherwise.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function answerCaptcha(string|PeerDatabaseRecord $peerUuid, string $answer): bool
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            // Return false if the captcha does not exist
            if(!self::captchaExists($peerUuid))
            {
                return false;
            }

            $captcha = self::getCaptcha($peerUuid);

            // Return false if the captcha has already been solved
            if($captcha->getStatus() === CaptchaStatus::SOLVED)
            {
                return false;
            }

            // Return false if the captcha is older than 5 minutes
            if ($captcha->isExpired())
            {
                return false;
            }

            // Verify the answer
            if($captcha->getAnswer() !== $answer)
            {
                return false;
            }

            $statement = Database::getConnection()->prepare("UPDATE captcha_images SET status='SOLVED', answered=NOW() WHERE peer_uuid=?");
            $statement->bindParam(1, $peerUuid);

            try
            {
                $statement->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to update a captcha in the database', $e);
            }

            return true;
        }

        /**
         * Retrieves the captcha record for the given peer UUID.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to retrieve the captcha for.
         * @return CaptchaRecord|null The captcha record.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function getCaptcha(string|PeerDatabaseRecord $peerUuid): ?CaptchaRecord
        {
            // If the peer_uuid is a RegisteredPeerRecord, get the UUID
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            Logger::getLogger()->debug('Getting the captcha record for peer ' . $peerUuid);

            try
            {
                $statement = Database::getConnection()->prepare("SELECT * FROM captcha_images WHERE peer_uuid=? LIMIT 1");
                $statement->bindParam(1, $peerUuid);
                $statement->execute();
                $result = $statement->fetch();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to get a captcha from the database', $e);
            }

            if($result === false)
            {
                return null;
            }

            return CaptchaRecord::fromArray($result);
        }

        /**
         * Checks if a captcha exists for the given peer UUID.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to check for a captcha.
         * @return bool True if a captcha exists, false otherwise.
         * @throws DatabaseOperationException If the operation fails.
         */
        public static function captchaExists(string|PeerDatabaseRecord $peerUuid): bool
        {
            // If the peer_uuid is a RegisteredPeerRecord, get the UUID
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            Logger::getLogger()->debug('Checking if a captcha exists for peer ' . $peerUuid);

            try
            {
                $statement = Database::getConnection()->prepare("SELECT COUNT(*) FROM captcha_images WHERE peer_uuid=?");
                $statement->bindParam(1, $peerUuid);
                $statement->execute();
                $result = $statement->fetchColumn();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to check if a captcha exists in the database', $e);
            }

            return $result > 0;
        }
    }