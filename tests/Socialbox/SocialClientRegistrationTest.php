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
            $this->assertFalse($rpcClient->getSessionState()->isAuthenticated());

            $this->assertFalse($rpcClient->verificationPasswordAuthentication('IncorrectPassword'));
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));
            $this->assertTrue($rpcClient->getSessionState()->containsFlag(SessionFlags::VER_PASSWORD));
            $this->assertFalse($rpcClient->getSessionState()->isAuthenticated());
        }

        /**
         * @throws DatabaseOperationException`
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
            $this->assertFalse($rpcClient->getSessionState()->isAuthenticated());

            $this->assertTrue($rpcClient->verificationPasswordAuthentication('SecuredTestingPassword123'));
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::AUTHENTICATION_REQUIRED));
            $this->assertFalse($rpcClient->getSessionState()->containsFlag(SessionFlags::VER_PASSWORD));
            $this->assertTrue($rpcClient->getSessionState()->isAuthenticated());
        }
    }
