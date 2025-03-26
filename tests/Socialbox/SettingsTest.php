<?php

    namespace Socialbox;

    use Helper;
    use PHPUnit\Framework\TestCase;
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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'malformedInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'malformedInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'malformedInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'malformedInputTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

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
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'malformedTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

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
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'malformedTest');

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
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'malformedTest');

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
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

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
            $rpcClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'malformedTest');

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
    }