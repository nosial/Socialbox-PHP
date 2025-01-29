<?php

    namespace Socialbox;

    use PHPUnit\Framework\TestCase;
    use Socialbox\Classes\ServerResolver;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\Types\InformationFieldName;

    class SocialClientTest extends TestCase
    {
        private const string COFFEE_DOMAIN = 'coffee.com';
        private const string TEAPOT_DOMAIN = 'teapot.com';


        protected function setUp(): void
        {
            putenv('LOG_LEVEL=debug');

            // Add mocked records for the test domains
            ServerResolver::addMock('coffee.com', 'v=socialbox;sb-rpc=http://127.0.0.0:8086/;sb-key=sig:g59Cf8j1wmQmRg1MkveYbpdiZ-1-_hFU9eRRJmQAwmc;sb-exp=0');
            ServerResolver::addMock('teapot.com', 'v=socialbox;sb-rpc=http://127.0.0.0:8087/;sb-key=sig:MDXUuripAo_IAv-EZTEoFhpIdhsXxfMLNunSnQzxYiY;sb-exp=0');
        }

        /**
         * Generates a random username based on the given domain.
         *
         * @param string $domain The domain to be appended to the generated username.
         * @return string Returns a randomly generated username in the format 'user<randomString>@<domain>'.
         */
        private static function generateUsername(string $domain): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';

            for ($i = 0; $i < 16; $i++)
            {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            return 'user' . $randomString . '@' . $domain;
        }

        private static function registerUser(string $domain, string $displayName): SocialClient
        {
            $client = new SocialClient(self::generateUsername($domain));
            $client->settingsSetPassword("password");
            $client->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, $displayName);
            return $client;
        }

        public function testRegistration(): void
        {
            $coffeeClient = new SocialClient(self::generateUsername(self::COFFEE_DOMAIN));

            // Check initial session state
            $this->assertFalse($coffeeClient->getSessionState()->isAuthenticated());
            $this->assertTrue($coffeeClient->getSessionState()->containsFlag(SessionFlags::REGISTRATION_REQUIRED));
            $this->assertTrue($coffeeClient->getSessionState()->containsFlag(SessionFlags::SET_PASSWORD));
            $this->assertTrue($coffeeClient->getSessionState()->containsFlag(SessionFlags::SET_DISPLAY_NAME));

            // Check progressive session state
            $this->assertTrue($coffeeClient->settingsSetPassword('coffeePassword'));
            $this->assertFalse($coffeeClient->getSessionState()->containsFlag(SessionFlags::SET_PASSWORD));
            $this->assertTrue($coffeeClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Coffee User'));
            $this->assertFalse($coffeeClient->getSessionState()->containsFlag(SessionFlags::SET_DISPLAY_NAME));

            $this->assertFalse($coffeeClient->getSessionState()->containsFlag(SessionFlags::REGISTRATION_REQUIRED));
            $this->assertTrue($coffeeClient->getSessionState()->isAuthenticated());
        }

        public function testResolveDecentralizedPeer(): void
        {
            $coffeeUser = self::registerUser(self::COFFEE_DOMAIN, "Coffee Lover");
            $this->assertTrue($coffeeUser->getSessionState()->isAuthenticated());
            $teapotUser = self::registerUser(self::TEAPOT_DOMAIN, "Tea & Biscuits");
            $this->assertTrue($teapotUser->getSessionState()->isAuthenticated());

            $coffeePeer = $coffeeUser->resolvePeer($teapotUser->getIdentifiedAs());
        }
    }
