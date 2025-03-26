<?php

    namespace Socialbox;

    use Exception;
    use Helper;
    use PHPUnit\Framework\TestCase;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;

    class SocialClientTest extends TestCase
    {

        public function testCoffeePing(): void
        {
            try
            {
                $rpcClient = new SocialClient(Helper::generateRandomPeer(COFFEE_DOMAIN, prefix: 'pingTest'));
                $this->assertTrue($rpcClient->ping(), sprintf('Failed to ping %s', COFFEE_DOMAIN));
            }
            catch (Exception $e)
            {
                $this->fail('Failed to create RPC client: ' . $e->getMessage());
            }
        }

        public function testTeapotPing(): void
        {
            try
            {
                $rpcClient = new SocialClient(Helper::generateRandomPeer(TEAPOT_DOMAIN, prefix: 'pingTest'));
                $this->assertTrue($rpcClient->ping(), sprintf('Failed to ping %s', TEAPOT_DOMAIN));
            }
            catch (Exception $e)
            {
                $this->fail('Failed to create RPC client: ' . $e->getMessage());
            }
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
    }
