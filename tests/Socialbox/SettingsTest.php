<?php

    namespace Socialbox;

    use Helper;
    use PHPUnit\Framework\TestCase;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\OtpCryptography;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;

    class SettingsTest extends TestCase
    {

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldDisplayName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldDisplayName');

            $displayName = Helper::generateRandomString(32);
            $rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, $displayName);
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals($displayName, $rpcClient->settingsGetInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidDisplayName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldInvalidDisplayName');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, Helper::generateRandomString(2048));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::DISPLAY_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::DISPLAY_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldFirstName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldFirstName');

            $firstName = Helper::generateRandomString(32);
            $rpcClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, $firstName);
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertEquals($firstName, $rpcClient->settingsGetInformationField(InformationFieldName::FIRST_NAME)->getValue());
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidFirstName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldInvalidFirstName');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, Helper::generateRandomString(2012));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::FIRST_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::FIRST_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldMiddleName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldMiddleName');

            $middleName = Helper::generateRandomString(32);
            $rpcClient->settingsAddInformationField(InformationFieldName::MIDDLE_NAME, $middleName);
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::MIDDLE_NAME));
            $this->assertEquals($middleName, $rpcClient->settingsGetInformationField(InformationFieldName::MIDDLE_NAME)->getValue());
        }


        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidMiddleName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldInvalidMiddleName');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::MIDDLE_NAME, Helper::generateRandomString(2012));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::MIDDLE_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::MIDDLE_NAME, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::MIDDLE_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldLastName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldLastName');

            $lastName = Helper::generateRandomString(32);
            $rpcClient->settingsAddInformationField(InformationFieldName::LAST_NAME, $lastName);
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertEquals($lastName, $rpcClient->settingsGetInformationField(InformationFieldName::LAST_NAME)->getValue());
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidLastName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldInvalidLastName');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::LAST_NAME, Helper::generateRandomString(2012));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::LAST_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::LAST_NAME, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::LAST_NAME));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldPhoneNumber(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldPhoneNumber');

            $phoneNumber = sprintf('+%d', Helper::generateRandomNumber(12));
            $rpcClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, $phoneNumber);
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::PHONE_NUMBER));
            $this->assertEquals($phoneNumber, $rpcClient->settingsGetInformationField(InformationFieldName::PHONE_NUMBER)->getValue());
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidPhoneNumber(): void
        {
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'testInformationFieldInvalidPhoneNumber');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, Helper::generateRandomString(2048));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::PHONE_NUMBER));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, Helper::generateRandomNumber(152));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::PHONE_NUMBER));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, Helper::generateRandomNumber(2));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::PHONE_NUMBER));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::PHONE_NUMBER));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldEmailAddress(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldEmailAddress');

            $rpcClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'testing@example.com');
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            $this->assertEquals('testing@example.com', $rpcClient->settingsGetInformationField(InformationFieldName::EMAIL_ADDRESS)->getValue());
        }


        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidEmailAddress(): void
        {
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'testInformationFieldInvalidEmailAddress');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, Helper::generateRandomString(2048));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldUrl(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

            $rpcClient->settingsAddInformationField(InformationFieldName::URL, 'https://example.com');
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::URL));
            $this->assertEquals('https://example.com', $rpcClient->settingsGetInformationField(InformationFieldName::URL)->getValue());
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidUrl(): void
        {
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'testInformationFieldInvalidUrl');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::URL, Helper::generateRandomString(2048));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::URL));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::URL, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::URL));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldBirthday(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInformationFieldBirthday');

            $rpcClient->settingsAddInformationField(InformationFieldName::BIRTHDAY, '2021-01-01');
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::BIRTHDAY));
            $this->assertEquals('2021-01-01', $rpcClient->settingsGetInformationField(InformationFieldName::BIRTHDAY)->getValue());
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldInvalidBirthday(): void
        {
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'testInformationFieldInvalidBirthday');

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::BIRTHDAY, Helper::generateRandomString(2048));
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::BIRTHDAY));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }

            try
            {
                $rpcClient->settingsAddInformationField(InformationFieldName::BIRTHDAY, '');
                $this->assertFalse($rpcClient->settingsInformationFieldExists(InformationFieldName::BIRTHDAY));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(-1001, $e->getCode(), sprintf('Unexpected error code: %d', $e->getCode()));
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInvalidInformationField(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInvalidInformationField');
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $testClient->settingsAddInformationField('Invalid', 'foo bar');
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         * @noinspection HttpUrlsUsage
         */
        public function testInvalidInformationFieldPrivacy(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'invalidInformationFieldPrivacyTest');
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $testClient->settingsAddInformationField(InformationFieldName::URL, 'http://example.com/', 'Invalid Privacy');
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testDeleteRequiredInformationField(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testDeleteRequiredInformationField');
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $testClient->settingsDeleteInformationField(InformationFieldName::DISPLAY_NAME);
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testDeleteInformationField(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'testDeleteInformationField');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'John', PrivacyState::PUBLIC));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceDeleteInformationFieldTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith'));
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'Alice', PrivacyState::PUBLIC));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());

            $johnResolved = $aliceClient->resolvePeer($johnClient->getIdentifiedAs());
            $this->assertNotNull($johnResolved);
            $this->assertCount(2, $johnResolved->getInformationFields());
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals('John Doe', $johnResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertEquals('John', $johnResolved->getInformationField(InformationFieldName::FIRST_NAME)->getValue());

            $aliceResolved = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertNotNull($aliceResolved);
            $this->assertCount(2, $aliceResolved->getInformationFields());
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals('Alice Smith', $aliceResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertEquals('Alice', $aliceResolved->getInformationField(InformationFieldName::FIRST_NAME)->getValue());

            $aliceClient->settingsDeleteInformationField(InformationFieldName::FIRST_NAME);
            $johnClient->settingsDeleteInformationField(InformationFieldName::FIRST_NAME);

            $johnResolved = $aliceClient->resolvePeer($johnClient->getIdentifiedAs());
            $this->assertNotNull($johnResolved);
            $this->assertCount(1, $johnResolved->getInformationFields());
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals('John Doe', $johnResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());

            $aliceResolved = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertNotNull($aliceResolved);
            $this->assertCount(1, $aliceResolved->getInformationFields());
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals('Alice Smith', $aliceResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testDeleteRequiredPassword(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'deleteRequiredPassword');
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $testClient->settingsDeletePassword();
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testSettingsSetOtp(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testSetOtp');
            $testAddress = $testClient->getIdentifiedAs();
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $totpUri = $testClient->settingsSetOtp('SecretTestingPassword123');
            $this->assertNotEmpty($totpUri);

            $testClient = new SocialClient($testAddress);
            $this->assertFalse($testClient->getSessionState()->isAuthenticated());
            $this->assertTrue($testClient->getSessionState()->containsFlag(SessionFlags::VER_OTP));
            $this->assertTrue($testClient->getSessionState()->containsFlag(SessionFlags::VER_PASSWORD));

            $this->assertTrue($testClient->verificationPasswordAuthentication('SecretTestingPassword123'));

            // Parse the TOTP URI
            $parsedUri = parse_url($totpUri);
            parse_str($parsedUri['query'], $queryParams);

            // Extract secret and other parameters
            $secret = $queryParams['secret'];
            $algorithm = strtolower(str_replace('SHA', 'sha', $queryParams['algorithm'] ?? 'sha512'));
            $digits = (int)($queryParams['digits'] ?? 6);
            $period = (int)($queryParams['period'] ?? 30);

            // Generate the OTP
            $otp = OtpCryptography::generateOTP(
                $secret,
                $period,
                $digits,
                null,
                $algorithm
            );

            // Verify the OTP
            $this->assertTrue($testClient->verificationOtpAuthentication($otp));
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testSettingsAddMultipleSigningKeys(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testSettingsAddMultipleSigningKeys');
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $signingKeys = [];
            for($i = 0; $i < 20; $i++)
            {
                $signingKeypair = Cryptography::generateSigningKeyPair();
                $signatureUuid = $testClient->settingsAddSignature($signingKeypair->getPublicKey());
                $this->assertNotNull($signatureUuid);
                $signingKeys[$signatureUuid] = $signingKeypair;
            }

            $this->assertCount(20, $testClient->settingsGetSignatures());

            // Verify all the signatures
            foreach($signingKeys as $signatureUuid => $signingKeypair)
            {
                $signature = $testClient->settingsGetSignature($signatureUuid);
                $this->assertNotNull($signature);
                $this->assertEquals($signingKeypair->getPublicKey(), $signature->getPublicKey());
            }

            // Delete the first 5 signatures
            $deletedSignatures = array_slice($signingKeys, 0, 10);
            foreach($deletedSignatures as $signatureUuid => $signingKeypair)
            {
                $this->assertTrue($testClient->settingsDeleteSignature($signatureUuid));
            }

            // Verify the remaining signatures
            $remainingSignatures = array_slice($signingKeys, 10);
            foreach($remainingSignatures as $signatureUuid => $signingKeypair)
            {
                $signature = $testClient->settingsGetSignature($signatureUuid);
                $this->assertNotNull($signature);
                $this->assertEquals($signingKeypair->getPublicKey(), $signature->getPublicKey());
            }

            $this->assertCount(10, $testClient->settingsGetSignatures());
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testSettingsAddExceedingSigningKeys(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testSettingsAddExceedingSigningKeys');
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $signingKeys = [];
            $this->expectException(RpcException::class);
            $this->expectExceptionCode(StandardError::FORBIDDEN->value);
            for($i = 0; $i < 25; $i++)
            {
                $signingKeypair = Cryptography::generateSigningKeyPair();
                $signatureUuid = $testClient->settingsAddSignature($signingKeypair->getPublicKey());
                $this->assertNotNull($signatureUuid);
                $signingKeys[$signatureUuid] = $signingKeypair;
            }
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testGetInformationFields(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testGetInformationFields');
            $phoneNumber = sprintf('+%d', Helper::generateRandomNumber(12));
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe', PrivacyState::PUBLIC));
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'John', PrivacyState::PUBLIC));
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Doe', PrivacyState::PUBLIC));
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'johndoe@example.com', PrivacyState::CONTACTS));
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, $phoneNumber, PrivacyState::TRUSTED));
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::BIRTHDAY, '1978-16-05', PrivacyState::TRUSTED));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());

            $informationFields = $testClient->settingsGetInformationFields();
            $this->assertCount(6, $informationFields);

            foreach($informationFields as $informationField)
            {
                switch($informationField->getName())
                {
                    case InformationFieldName::DISPLAY_NAME:
                        $this->assertEquals('John Doe', $informationField->getValue());
                        $this->assertEquals(PrivacyState::PUBLIC, $informationField->getPrivacyState());
                        break;

                    case InformationFieldName::FIRST_NAME:
                        $this->assertEquals('John', $informationField->getValue());
                        $this->assertEquals(PrivacyState::PUBLIC, $informationField->getPrivacyState());
                        break;

                    case InformationFieldName::LAST_NAME:
                        $this->assertEquals('Doe', $informationField->getValue());
                        $this->assertEquals(PrivacyState::PUBLIC, $informationField->getPrivacyState());
                        break;

                    case InformationFieldName::EMAIL_ADDRESS:
                        $this->assertEquals('johndoe@example.com', $informationField->getValue());
                        $this->assertEquals(PrivacyState::CONTACTS, $informationField->getPrivacyState());
                        break;

                    case InformationFieldName::PHONE_NUMBER:
                        $this->assertEquals($phoneNumber, $informationField->getValue());
                        $this->assertEquals(PrivacyState::TRUSTED, $informationField->getPrivacyState());
                        break;

                    case InformationFieldName::BIRTHDAY:
                        $this->assertEquals('1978-16-05', $informationField->getValue());
                        $this->assertEquals(PrivacyState::TRUSTED, $informationField->getPrivacyState());
                        break;

                    default:
                        $this->fail(sprintf('Unexpected information field: %s', $informationField->getName()->value));
                }
            }
        }

        public function testSettingsUpdatePassword(): void
        {
            $testClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testSettingsAddExceedingSigningKeys');
            $this->assertTrue($testClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($testClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());
            $this->assertTrue($testClient->settingsUpdatePassword('NewPassword123', 'SecretTestingPassword123'));

            $testClient = new SocialClient($testClient->getIdentifiedAs());
            $this->assertFalse($testClient->getSessionState()->isAuthenticated());
            $this->assertTrue($testClient->verificationPasswordAuthentication('NewPassword123'));
            $this->assertTrue($testClient->getSessionState()->isAuthenticated());
        }
    }