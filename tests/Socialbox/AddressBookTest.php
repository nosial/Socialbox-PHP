<?php

    namespace Socialbox;

    use Helper;
    use PHPUnit\Framework\TestCase;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;

    class AddressBookTest extends TestCase
    {
        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testAddressBookAdd(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith');
            $aliceClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());

            $johnClient->addressBookAddContact($aliceClient->getIdentifiedAs());
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));

            $aliceClient->addressBookAddContact($johnClient->getIdentifiedAs());
            $this->assertTrue($aliceClient->addressBookContactExists($johnClient->getIdentifiedAs()));
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookDelete(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith');
            $aliceClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());

            $this->assertTrue($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookDeleteContact($aliceClient->getIdentifiedAs()));
            $this->assertFalse($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));

            $this->assertTrue($aliceClient->addressBookAddContact($johnClient->getIdentifiedAs()));
            $this->assertTrue($aliceClient->addressBookContactExists($johnClient->getIdentifiedAs()));
            $this->assertTrue($aliceClient->addressBookDeleteContact($johnClient->getIdentifiedAs()));
            $this->assertFalse($aliceClient->addressBookContactExists($johnClient->getIdentifiedAs()));
        }
    }