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

            $this->expectException(RpcException::class);
            $this->expectExceptionCode(StandardError::FORBIDDEN->value);
            for($i = 0; $i < 25; $i++)
            {
                $signingKeypair = Cryptography::generateSigningKeyPair();
                $signatureUuid = $testClient->settingsAddSignature($signingKeypair->getPublicKey());
                $this->assertNotNull($signatureUuid);
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

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
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

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInformationFieldWithMaximumLengthValues(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testMaxLengthValues');

            // Testing with maximum allowed lengths (assuming 255 characters is the max)
            $maxLengthString = Helper::generateRandomString(255);
            $this->expectException(RpcException::class);
            $this->expectExceptionCode(StandardError::RPC_INVALID_ARGUMENTS->value);
            $rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, $maxLengthString);
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testSettingsPrivacyStateChanges(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testPrivacyChanges');
            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($rpcClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());

            // Add field with initial privacy setting
            $rpcClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'john@example.com', PrivacyState::PRIVATE);
            $this->assertEquals(
                PrivacyState::PRIVATE,
                $rpcClient->settingsGetInformationField(InformationFieldName::EMAIL_ADDRESS)->getPrivacyState()
            );

            // Update to different privacy settings
            $this->assertTrue($rpcClient->settingsUpdateInformationPrivacy(InformationFieldName::EMAIL_ADDRESS, PrivacyState::PUBLIC));
            $this->assertEquals(
                PrivacyState::PUBLIC,
                $rpcClient->settingsGetInformationField(InformationFieldName::EMAIL_ADDRESS)->getPrivacyState()
            );

            // Update to CONTACTS privacy
            $this->assertTrue($rpcClient->settingsUpdateInformationPrivacy(InformationFieldName::EMAIL_ADDRESS, PrivacyState::CONTACTS));
            $this->assertEquals(
                PrivacyState::CONTACTS,
                $rpcClient->settingsGetInformationField(InformationFieldName::EMAIL_ADDRESS)->getPrivacyState()
            );
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testInformationFieldValueUpdate(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testValueUpdate');
            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Initial Name'));
            $this->assertTrue($rpcClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());

            // Update the value of an existing field
            $this->assertTrue($rpcClient->settingsUpdateInformationField(InformationFieldName::DISPLAY_NAME, 'Updated Name'));
            $this->assertEquals('Updated Name', $rpcClient->settingsGetInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testInformationFieldSpecialCharacters(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testSpecialChars');
            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($rpcClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());

            // Test with various special characters
            $specialChars = "!@#$%^&*()_+{}|:<>?[];',./`~éñüÄß漢字";
            $rpcClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, $specialChars);
            $this->assertEquals($specialChars, $rpcClient->settingsGetInformationField(InformationFieldName::FIRST_NAME)->getValue());
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testMaliciousInformationFieldValues(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testMaliciousValues');
            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($rpcClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());

            // Test with SQL injection attempt
            $sqlInjection = "Robert'); DROP TABLE users;--";
            $rpcClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, $sqlInjection);
            $this->assertEquals($sqlInjection, $rpcClient->settingsGetInformationField(InformationFieldName::FIRST_NAME)->getValue());

            // Test with XSS attempt
            $xssAttempt = "<script>alert('XSS')</script>";
            $rpcClient->settingsAddInformationField(InformationFieldName::MIDDLE_NAME, $xssAttempt);
            $this->assertEquals($xssAttempt, $rpcClient->settingsGetInformationField(InformationFieldName::MIDDLE_NAME)->getValue());
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testNonAuthenticatedSettingsAccess(): void
        {
            // Create client but don't authenticate
            $client = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testNonAuthAccess');

            $this->expectException(RpcException::class);
            $this->expectExceptionCode(StandardError::METHOD_NOT_ALLOWED->value);
            $client->addressBookAddContact('johndoeExample@example.com');
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @noinspection HtmlUnknownTarget
         */
        public function testCrossSiteScriptingDefense(): void
        {
            $client = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testXssDefense');
            $this->assertTrue($client->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($client->settingsSetPassword('SecretPassword123'));
            $this->assertTrue($client->getSessionState()->isAuthenticated());

            // Test with more complex XSS payloads
            $xssPayloads = [
                '<img src="x" onerror="alert(\'XSS\')" alt="test">',
                '\"><script>alert(1)</script>',
                '"><iframe src="javascript:alert(\'XSS\')"></iframe>',
                'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//\'>'
            ];

            foreach ($xssPayloads as $index => $payload) {
                $this->expectException(RpcException::class);
                $this->expectExceptionCode(StandardError::RPC_INVALID_ARGUMENTS->value);
                $client->settingsAddInformationField(InformationFieldName::URL, $payload);
                $this->assertEquals($payload, $client->settingsGetInformationField(InformationFieldName::URL)->getValue());
            }
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testExtremelyLongValues(): void
        {
            $client = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testLongValues');
            $this->assertTrue($client->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($client->settingsSetPassword('SecretPassword123'));
            $this->assertTrue($client->getSessionState()->isAuthenticated());

            // Test with extremely long values (potential buffer overflow)
            $longString = Helper::generateRandomString(10000);

            try {
                $client->settingsAddInformationField(InformationFieldName::FIRST_NAME, $longString);
                $this->fail('Expected exception for extremely long value');
            } catch (RpcException $e) {
                $this->assertEquals(-1001, $e->getCode());
            }
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testPrivacyStateVisibilityEnforcement(): void
        {
            // Create two users - one to set fields with various privacy states
            $userA = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'userAPrivacy');
            $this->assertTrue($userA->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'User A'));
            $this->assertTrue($userA->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'Alpha', PrivacyState::PUBLIC));
            $this->assertTrue($userA->settingsAddInformationField(InformationFieldName::MIDDLE_NAME, 'Beta', PrivacyState::PRIVATE));
            $this->assertTrue($userA->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Gamma', PrivacyState::CONTACTS));
            $this->assertTrue($userA->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'alpha@example.com', PrivacyState::TRUSTED));
            $this->assertTrue($userA->settingsSetPassword('SecretPassword123'));

            // Create another user to try to access the fields
            $userB = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'userBPrivacy');
            $this->assertTrue($userB->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'User B'));
            $this->assertTrue($userB->settingsSetPassword('SecretPassword123'));

            // UserB resolves UserA and should only see PUBLIC fields
            $resolvedUser = $userB->resolvePeer($userA->getIdentifiedAs());
            $this->assertNotNull($resolvedUser);
            $this->assertTrue($resolvedUser->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertTrue($resolvedUser->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertFalse($resolvedUser->informationFieldExists(InformationFieldName::MIDDLE_NAME));
            $this->assertFalse($resolvedUser->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertFalse($resolvedUser->informationFieldExists(InformationFieldName::EMAIL_ADDRESS));

            // Now establish a contact relationship and retest
            // This depends on the implementation, but might be something like:
            $userA->addressBookAddContact($userB->getIdentifiedAs());
            $userB->addressBookAddContact($userA->getIdentifiedAs());

            $resolvedUserAfterContact = $userB->resolvePeer($userA->getIdentifiedAs());
            $this->assertTrue($resolvedUserAfterContact->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertTrue($resolvedUserAfterContact->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertFalse($resolvedUserAfterContact->informationFieldExists(InformationFieldName::MIDDLE_NAME));
            $this->assertTrue($resolvedUserAfterContact->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertFalse($resolvedUserAfterContact->informationFieldExists(InformationFieldName::EMAIL_ADDRESS));
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testUnicodeAndEmojis(): void
        {
            $client = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testUnicode');
            $this->assertTrue($client->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($client->settingsSetPassword('SecretPassword123'));
            $this->assertTrue($client->getSessionState()->isAuthenticated());

            // Test with various Unicode characters and emojis
            $unicodeNames = [
                '您好世界', // Chinese
                'Здравствуй, мир', // Russian
                'مرحبا بالعالم', // Arabic
                'こんにちは世界', // Japanese
                '안녕하세요 세계', // Korean
                '😀🌍🏆🚀🎉', // Emojis
                '🧑‍💻👨‍👩‍👧‍👦🏳️‍🌈' // Complex emojis with ZWJ sequences
            ];

            foreach ($unicodeNames as $index => $name) {
                $client->settingsUpdateInformationField(InformationFieldName::DISPLAY_NAME, $name);
                $this->assertEquals($name, $client->settingsGetInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            }
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testInvalidSigningKeyFormats(): void
        {
            $client = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'testInvalidKeys');
            $this->assertTrue($client->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($client->settingsSetPassword('SecretPassword123'));
            $this->assertTrue($client->getSessionState()->isAuthenticated());

            // Test with various invalid signing key formats
            $invalidKeys = [
                '', // Empty string
                'not-a-valid-key', // Plain text
                Helper::generateRandomString(32), // Random string that's not a valid key
                '<script>alert("XSS")</script>', // XSS attempt
            ];

            foreach ($invalidKeys as $invalidKey) {
                try {
                    $client->settingsAddSignature($invalidKey);
                    $this->fail('Expected exception for invalid signing key');
                } catch (RpcException $e) {
                    // The error code might vary based on implementation
                    $this->assertTrue($e->getCode() < 0);
                }
            }
        }
    }