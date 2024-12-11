<?php

    namespace Socialbox\Managers;

    use PDOException;
    use Random\RandomException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\SecuredPassword;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\EncryptionRecord;

    class EncryptionRecordsManager
    {
        private const int KEY_LENGTH = 256; // Increased key length

        /**
         * Retrieves the total count of records in the encryption_records table.
         *
         * @return int The number of records in the encryption_records table.
         * @throws DatabaseOperationException If a database operation error occurs while fetching the record count.
         */
        public static function getRecordCount(): int
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM encryption_records');
                $stmt->execute();
                return $stmt->fetchColumn();
            }
            catch (PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve encryption record count', $e);
            }
        }

        /**
         * Inserts a new encryption record into the encryption_records table.
         *
         * @param EncryptionRecord $record The encryption record to insert, containing data, IV, and tag.
         * @return void
         * @throws DatabaseOperationException If the insertion into the database fails.
         */
        private static function insertRecord(EncryptionRecord $record): void
        {
            try
            {
                $stmt = Database::getConnection()->prepare('INSERT INTO encryption_records (data, iv, tag) VALUES (?, ?, ?)');

                $data = $record->getData();
                $stmt->bindParam(1, $data);

                $iv = $record->getIv();
                $stmt->bindParam(2, $iv);

                $tag = $record->getTag();
                $stmt->bindParam(3, $tag);

                $stmt->execute();
            }
            catch(PDOException $e)
            {

                throw new DatabaseOperationException('Failed to insert encryption record into the database', $e);
            }
        }

        /**
         * Retrieves a random encryption record from the database.
         *
         * @return EncryptionRecord An instance of EncryptionRecord containing the data of a randomly selected record.
         * @throws DatabaseOperationException If an error occurs while attempting to retrieve the record from the database.
         */
        public static function getRandomRecord(): EncryptionRecord
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_records ORDER BY RAND() LIMIT 1');
                $stmt->execute();
                $data = $stmt->fetch();

                return new EncryptionRecord($data);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve a random encryption record', $e);
            }
        }

        /**
         * Retrieves all encryption records from the database.
         *
         * @return EncryptionRecord[] An array of EncryptionRecord instances, each representing a record from the database.
         * @throws DatabaseOperationException If an error occurs while attempting to retrieve the records from the database.
         */
        public static function getAllRecords(): array
        {
            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM encryption_records');
                $stmt->execute();
                $data = $stmt->fetchAll();

                $records = [];
                foreach ($data as $record)
                {
                    $records[] = new EncryptionRecord($record);
                }

                return $records;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException('Failed to retrieve all encryption records', $e);
            }
        }

        /**
         * Generates encryption records and inserts them into the database until the specified total count is reached.
         *
         * @param int $count The total number of encryption records desired in the database.
         * @return int The number of new records that were created and inserted.
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public static function generateRecords(int $count): int
        {
            $currentCount = self::getRecordCount();
            if($currentCount >= $count)
            {
                return 0;
            }

            $created = 0;
            for($i = 0; $i < $count - $currentCount; $i++)
            {
                self::insertRecord(self::generateEncryptionRecord());
                $created++;
            }

            return $created;
        }

        /**
         * Generates a new encryption record containing a key, pepper, and salt.
         *
         * @return EncryptionRecord An instance of EncryptionRecord containing an encrypted structure
         *                          with the generated key, pepper, and salt.
         * @throws CryptographyException If random byte generation fails during the creation of the encryption record.
         */
        private static function generateEncryptionRecord(): EncryptionRecord
        {
            try
            {
                $key = random_bytes(self::KEY_LENGTH / 8);
                $pepper = bin2hex(random_bytes(SecuredPassword::PEPPER_LENGTH / 2));
                $salt = bin2hex(random_bytes(self::KEY_LENGTH / 16));

            }
            catch (RandomException $e)
            {
                throw new CryptographyException("Random bytes generation failed", $e->getCode(), $e);
            }

            return self::encrypt(['key' => base64_encode($key), 'pepper' => $pepper, 'salt' => $salt,]);
        }

        /**
         * Encrypts the given vault item and returns an EncryptionRecord containing the encrypted data.
         *
         * @param array $vaultItem The associative array representing the vault item to be encrypted.
         * @return EncryptionRecord An instance of EncryptionRecord containing the encrypted vault data, initialization vector (IV), and authentication tag.
         * @throws CryptographyException If the initialization vector generation or vault encryption process fails.
         */
        private static function encrypt(array $vaultItem): EncryptionRecord
        {
            $serializedVault = json_encode($vaultItem);

            try
            {
                $iv = random_bytes(openssl_cipher_iv_length(SecuredPassword::ENCRYPTION_ALGORITHM));
            }
            catch (RandomException $e)
            {
                throw new CryptographyException("IV generation failed", $e->getCode(), $e);
            }
            $tag = null;

            $encryptedVault = openssl_encrypt($serializedVault, SecuredPassword::ENCRYPTION_ALGORITHM,
                Configuration::getInstanceConfiguration()->getRandomEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag
            );

            if ($encryptedVault === false)
            {
                throw new CryptographyException("Vault encryption failed");
            }

            return new EncryptionRecord([
                'data' => base64_encode($encryptedVault),
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
            ]);
        }
    }