<?php

    namespace Socialbox;

    use Helper;
    use PHPUnit\Framework\TestCase;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;

    class SocialClientRegistrationTest extends TestCase
    {

        /**
         * @throws CryptographyException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws RpcException
         */
        public function testCreateAccount(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'createTest');

            $sessionState = $rpcClient->getSessionState();
            $this->assertFalse($sessionState->isAuthenticated());
            $this->assertTrue($sessionState->containsFlag(SessionFlags::REGISTRATION_REQUIRED));

            foreach($rpcClient->getSessionState()->getFlags() as $sessionFlag)
            {
                if(SessionFlags::tryFrom($sessionFlag) === null)
                {
                    $this->fail('Unknown session flag: ' . $sessionFlag);
                }
            }

            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Test User'));
            $this->assertEquals('Test User', $rpcClient->settingsGetInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::SET_DISPLAY_NAME));

            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'John'));
            $this->assertEquals('John', $rpcClient->settingsGetInformationField(InformationFieldName::FIRST_NAME)->getValue());
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::SET_FIRST_NAME));

            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Doe'));
            $this->assertEquals('Doe', $rpcClient->settingsGetInformationField(InformationFieldName::LAST_NAME)->getValue());
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::SET_LAST_NAME));

            $this->assertTrue($rpcClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'johndoe@example.com'));
            $this->assertEquals('johndoe@example.com', $rpcClient->settingsGetInformationField(InformationFieldName::EMAIL_ADDRESS)->getValue());
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::SET_EMAIL));

            $this->assertTrue($rpcClient->settingsSetPassword('SecuredTestingPassword123'));
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::SET_PASSWORD));

            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testInvalidDisplayName(): void
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
        public function testValidDisplayName(): void
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
        public function testInvalidFirstName(): void
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
        public function testValidFirstName(): void
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
        public function testInvalidMiddleName(): void
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
        public function testValidMiddleName(): void
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
        public function testInvalidLastName(): void
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
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testValidLastName(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

            $lastName = Helper::generateRandomString(32);
            $rpcClient->settingsAddInformationField(InformationFieldName::LAST_NAME, $lastName);
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertEquals($lastName, $rpcClient->settingsGetInformationField(InformationFieldName::LAST_NAME)->getValue());
        }

        public function testInvalidPhoneNumber(): void
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

        public function testValidPhoneNumber(): void
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
        public function testInvalidEmail(): void
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
        public function testValidEmail(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

            $rpcClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'testing@example.com');
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            $this->assertEquals('testing@example.com', $rpcClient->settingsGetInformationField(InformationFieldName::EMAIL_ADDRESS)->getValue());
        }

        public function testInvalidUrl(): void
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

        public function testValidUrl(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'validInputTest');

            $rpcClient->settingsAddInformationField(InformationFieldName::URL, 'https://example.com');
            $this->assertTrue($rpcClient->settingsInformationFieldExists(InformationFieldName::URL));
            $this->assertEquals('https://example.com', $rpcClient->settingsGetInformationField(InformationFieldName::URL)->getValue());
        }

        public function testInvalidBirthday(): void
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

        public function testValidBirthday(): void
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
        public function testPeerResolution(): void
        {
            $johnClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'johnDoe');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Test User');
            $this->assertEquals('Test User', $johnClient->settingsGetInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $johnClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'John');
            $this->assertEquals('John', $johnClient->settingsGetInformationField(InformationFieldName::FIRST_NAME)->getValue());
            $johnClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Doe');
            $this->assertEquals('Doe', $johnClient->settingsGetInformationField(InformationFieldName::LAST_NAME)->getValue());
            $johnClient->settingsSetPassword('SecuredTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());
            $this->assertFalse($johnClient->getSessionState()->containsFlag(SessionFlags::REGISTRATION_REQUIRED));
            $this->assertFalse($johnClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceSmith');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Test User');
            $this->assertEquals('Test User', $aliceClient->settingsGetInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $aliceClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'Alice');
            $this->assertEquals('Alice', $aliceClient->settingsGetInformationField(InformationFieldName::FIRST_NAME)->getValue());
            $aliceClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Smith');
            $this->assertEquals('Smith', $aliceClient->settingsGetInformationField(InformationFieldName::LAST_NAME)->getValue());
            $aliceClient->settingsSetPassword('SecuredTestingPassword123');
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());
            $this->assertFalse($aliceClient->getSessionState()->containsFlag(SessionFlags::REGISTRATION_REQUIRED));
            $this->assertFalse($aliceClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));

            $aliceResolved = $aliceClient->resolvePeer($aliceClient->getSelf()->getPeerAddress());
            foreach($aliceResolved->getInformationFields() as $informationField)
            {
                switch($informationField->getName())
                {
                    case InformationFieldName::DISPLAY_NAME:
                        $this->assertEquals('Test User', $informationField->getValue());
                        break;
                    case InformationFieldName::FIRST_NAME:
                        $this->assertEquals('Alice', $informationField->getValue());
                        break;
                    case InformationFieldName::LAST_NAME:
                        $this->assertEquals('Smith', $informationField->getValue());
                        break;
                    default:
                        $this->fail('Unexpected information field: ' . $informationField->getName()->value);
                }
            }

            $johnResolved = $aliceClient->resolvePeer($johnClient->getSelf()->getPeerAddress());
            foreach($johnResolved->getInformationFields() as $informationField)
            {
                switch($informationField->getName())
                {
                    case InformationFieldName::DISPLAY_NAME:
                        $this->assertEquals('Test User', $informationField->getValue());
                        break;
                    case InformationFieldName::FIRST_NAME:
                        $this->assertEquals('John', $informationField->getValue());
                        break;
                    case InformationFieldName::LAST_NAME:
                        $this->assertEquals('Doe', $informationField->getValue());
                        break;
                    default:
                        $this->fail('Unexpected information field: ' . $informationField->getName()->value);
                }
            }

            $this->assertEquals($johnClient->getSelf()->getPeerAddress(), $johnResolved->getPeerAddress());
            $this->assertEquals($aliceClient->getSelf()->getPeerAddress(), $aliceResolved->getPeerAddress());
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testIncorrectLogin(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'incorrectLogin');
            $generatedPeer = $rpcClient->getIdentifiedAs();
            $rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Test User');
            $rpcClient->settingsSetPassword('SecuredTestingPassword123');
            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());

            $rpcClient = new SocialClient($generatedPeer);
            $this->assertFalse($rpcClient->getSessionState()->isAuthenticated());
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::VER_PASSWORD));

            $this->assertFalse($rpcClient->verificationPasswordAuthentication('IncorrectPassword'));
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::VER_PASSWORD));
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testCorrectLogin(): void
        {
            $rpcClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'incorrectLogin');
            $generatedPeer = $rpcClient->getIdentifiedAs();
            $rpcClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Test User');
            $rpcClient->settingsSetPassword('SecuredTestingPassword123');
            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());

            $rpcClient = new SocialClient($generatedPeer);
            $this->assertFalse($rpcClient->getSessionState()->isAuthenticated());
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::VER_PASSWORD));

            $this->assertTrue($rpcClient->verificationPasswordAuthentication('SecuredTestingPassword123'));
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::VER_PASSWORD));
        }
    }
