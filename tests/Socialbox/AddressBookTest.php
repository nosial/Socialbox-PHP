<?php

    namespace Socialbox;

    use Helper;
    use PHPUnit\Framework\TestCase;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\Standard\Contact;

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
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testAddressBookAddInvalidAddress(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $this->expectExceptionCode(StandardError::RPC_INVALID_ARGUMENTS->value);
            $johnClient->addressBookAddContact('invalid invalid invalid');
        }

        /**
         * @throws ResolutionException
         * @throws RpcException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testAddressBookAddNonExistent(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            try
            {
                $this->assertFalse($johnClient->addressBookAddContact('phonyUser@teapot.com'));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(StandardError::PEER_NOT_FOUND->value, $e->getCode(), 'Expected error code -7000 (Peer not found) because the instance exists but the peer does not');
            }
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testAddressBookAddNonExistentService(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            try
            {
                $this->assertFalse($johnClient->addressBookAddContact('phonyUser@example.com'));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(StandardError::RESOLUTION_FAILED->value, $e->getCode(), 'Expected error code -301 (Resolution failed) because the instance does not exist');
            }
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

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookSigningKey(): void
        {
            // John
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());
            $johnSigningKeypair = Cryptography::generateSigningKeyPair();
            $johnSigningKeyUuid = $johnClient->settingsAddSignature($johnSigningKeypair->getPublicKey(), 'John Test Signature');
            $this->assertNotNull($johnSigningKeyUuid);

            // Alice
            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith'));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());
            $aliceSigningKeypair = Cryptography::generateSigningKeyPair();
            $aliceSigningKeyUuid = $aliceClient->settingsAddSignature($aliceSigningKeypair->getPublicKey(), 'Alice Test Signature');
            $this->assertNotNull($aliceSigningKeyUuid);

            // John trusts Alice's signing key
            $this->assertTrue($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookTrustSignature($aliceClient->getIdentifiedAs(), $aliceSigningKeyUuid));
            $aliceContact = $johnClient->addressBookGetContact($aliceClient->getIdentifiedAs());
            $this->assertNotEmpty($aliceContact->getKnownKeys());
            $this->assertTrue($aliceContact->signatureExists($aliceSigningKeyUuid));
            $this->assertTrue($aliceContact->signatureKeyExists($aliceSigningKeypair->getPublicKey()));
            $this->assertFalse($aliceContact->signatureExists($johnSigningKeyUuid));
            $this->assertFalse($aliceContact->signatureKeyExists($johnSigningKeypair->getPublicKey()));
            $aliceKnownSigningKeyTest = $aliceContact->getSignature($aliceSigningKeyUuid);
            $this->assertNotNull($aliceKnownSigningKeyTest);
            $this->assertEquals($aliceSigningKeyUuid, $aliceKnownSigningKeyTest->getUuid());
            $this->assertEquals($aliceSigningKeypair->getPublicKey(), $aliceKnownSigningKeyTest->getPublicKey());
            $this->assertEquals('Alice Test Signature', $aliceKnownSigningKeyTest->getName());

            // Alice trusts John's signing key
            $this->assertTrue($aliceClient->addressBookAddContact($johnClient->getIdentifiedAs()));
            $this->assertTrue($aliceClient->addressBookContactExists($johnClient->getIdentifiedAs()));
            $this->assertTrue($aliceClient->addressBookTrustSignature($johnClient->getIdentifiedAs(), $johnSigningKeyUuid));
            $johnContact = $aliceClient->addressBookGetContact($johnClient->getIdentifiedAs());
            $this->assertNotEmpty($johnContact->getKnownKeys());
            $this->assertTrue($johnContact->signatureExists($johnSigningKeyUuid));
            $this->assertTrue($johnContact->signatureKeyExists($johnSigningKeypair->getPublicKey()));
            $this->assertFalse($johnContact->signatureExists($aliceSigningKeyUuid));
            $this->assertFalse($johnContact->signatureKeyExists($aliceSigningKeypair->getPublicKey()));
            $johnKnownSigningKeyTest = $johnContact->getSignature($johnSigningKeyUuid);
            $this->assertNotNull($johnKnownSigningKeyTest);
            $this->assertEquals($johnSigningKeyUuid, $johnKnownSigningKeyTest->getUuid());
            $this->assertEquals($johnSigningKeypair->getPublicKey(), $johnKnownSigningKeyTest->getPublicKey());
            $this->assertEquals('John Test Signature', $johnKnownSigningKeyTest->getName());
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddHostAsContact(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $johnClient->addressBookAddContact(sprintf('%s@%s', ReservedUsernames::HOST->value, TEAPOT_DOMAIN));
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookTrustNonExistent(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $johnClient->addressBookUpdateRelationship(sprintf('phonyUser@%s', COFFEE_DOMAIN), ContactRelationshipType::TRUSTED);
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testAddSelfAsContact(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $this->expectException(RpcException::class);
            $johnClient->addressBookAddContact($johnClient->getIdentifiedAs());
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookInvalidSigningKey(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith'));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());

            $this->assertTrue($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));

            $this->expectException(RpcException::class);
            $johnClient->addressBookTrustSignature($aliceClient->getIdentifiedAs(), 'phonySignatureUuid');
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookDeleteNonExistent(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            try
            {
                $this->assertFalse($johnClient->addressBookDeleteContact('nonexistent@teapot.com'));
            }
            catch(RpcException $e)
            {
                $this->assertEquals(StandardError::PEER_NOT_FOUND->value, $e->getCode(), 'Expected error code -7000 (Peer not found) because the instance exists but the peer does not');
            }
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookGetContacts(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnContactsTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            // Add multiple contacts
            for ($i = 0; $i < 5; $i++) {
                $contactClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: "contact{$i}Test");
                $contactClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, "Contact {$i}");
                $contactClient->settingsSetPassword('ContactPassword123');
                $this->assertTrue($contactClient->getSessionState()->isAuthenticated());
                $contactAddress = $contactClient->getIdentifiedAs();

                $this->assertTrue($johnClient->addressBookAddContact($contactAddress));
                $this->assertTrue($johnClient->addressBookContactExists($contactAddress));
            }

            // Test getting all contacts
            $contacts = $johnClient->addressBookGetContacts();
            $this->assertCount(5, $contacts);
            $this->assertContainsOnlyInstancesOf(Contact::class, $contacts);


            // Test pagination
            $firstPageContacts = $johnClient->addressBookGetContacts(2, 1);
            $this->assertCount(1, $firstPageContacts);

            $secondPageContacts = $johnClient->addressBookGetContacts(1, 3);
            $this->assertCount(3, $secondPageContacts);

            // Make sure unique items are on per page
            $firstPageItems = $johnClient->addressBookGetContacts(1, 3);
            $secondPageItems = $johnClient->addressBookGetContacts(2, 3);

            $this->assertNotEquals($firstPageItems, $secondPageItems);
            foreach($firstPageItems as $firstPageItem) {
                foreach($secondPageItems as $secondPageItem) {
                    $this->assertNotEquals($firstPageItem->getAddress()->getAddress(), $secondPageItem->getAddress()->getAddress());
                }
            }
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookRevokeSignature(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());
            $johnSigningKeypair = Cryptography::generateSigningKeyPair();
            $johnSigningKeyUuid = $johnClient->settingsAddSignature($johnSigningKeypair->getPublicKey(), 'John Test Signature');
            $this->assertNotNull($johnSigningKeyUuid);

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith'));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());
            $aliceSigningKeypair = Cryptography::generateSigningKeyPair();
            $aliceSigningKeyUuid = $aliceClient->settingsAddSignature($aliceSigningKeypair->getPublicKey(), 'Alice Test Signature');
            $this->assertNotNull($aliceSigningKeyUuid);

            $this->assertTrue($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookTrustSignature($aliceClient->getIdentifiedAs(), $aliceSigningKeyUuid));
            $this->assertTrue($aliceClient->addressBookAddContact($johnClient->getIdentifiedAs()));
            $this->assertTrue($aliceClient->addressBookTrustSignature($johnClient->getIdentifiedAs(), $johnSigningKeyUuid));

            $this->assertTrue($aliceClient->addressBookRevokeSignature($johnClient->getIdentifiedAs(), $johnSigningKeyUuid));
            $johnContact = $aliceClient->addressBookGetContact($johnClient->getIdentifiedAs());
            $this->assertEmpty($johnContact->getKnownKeys());
            $this->assertFalse($johnContact->signatureExists($johnSigningKeyUuid));

            $this->assertTrue($johnClient->addressBookRevokeSignature($aliceClient->getIdentifiedAs(), $aliceSigningKeyUuid));
            $aliceContact = $johnClient->addressBookGetContact($aliceClient->getIdentifiedAs());
            $this->assertEmpty($aliceContact->getKnownKeys());
            $this->assertFalse($aliceContact->signatureExists($aliceSigningKeyUuid));
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookAddWithNonDefaultRelationship(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith'));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());

            $johnClient->addressBookAddContact($aliceClient->getIdentifiedAs(), ContactRelationshipType::TRUSTED);
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));
            $this->assertEquals(ContactRelationshipType::TRUSTED, $johnClient->addressBookGetContact($aliceClient->getIdentifiedAs())->getRelationship());

            $aliceClient->addressBookAddContact($johnClient->getIdentifiedAs(), ContactRelationshipType::TRUSTED);
            $this->assertTrue($aliceClient->addressBookContactExists($johnClient->getIdentifiedAs()));
            $this->assertEquals(ContactRelationshipType::TRUSTED, $aliceClient->addressBookGetContact($johnClient->getIdentifiedAs())->getRelationship());
        }

        /**
         * @throws RpcException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         */
        public function testAddressBookPrivacy(): void
        {
            // John setup
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe', PrivacyState::PUBLIC));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'johndoe@example.com', PrivacyState::CONTACTS));
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'John', PrivacyState::TRUSTED));
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Doe', PrivacyState::PRIVATE));
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, '+17712579155', PrivacyState::TRUSTED));

            // Alice setup
            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith', PrivacyState::PUBLIC));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'alicesmith@example.com', PrivacyState::CONTACTS));
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'Alice', PrivacyState::TRUSTED));
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Smith', PrivacyState::PRIVATE));
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, '+18193447227', PrivacyState::TRUSTED));

            // Bob and Alice setup (mutual)
            $bobClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'bobAddressBookTest');
            $this->assertTrue($bobClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Bob Johnson', PrivacyState::PUBLIC));
            $this->assertTrue($bobClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($bobClient->getSessionState()->isAuthenticated());
            $this->assertTrue($bobClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'bobjohnson@example.com', PrivacyState::CONTACTS));
            $this->assertTrue($bobClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'Bob', PrivacyState::TRUSTED));
            $this->assertTrue($bobClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Johnson', PrivacyState::PRIVATE));
            $this->assertTrue($bobClient->settingsAddInformationField(InformationFieldName::PHONE_NUMBER, '+18193447227', PrivacyState::TRUSTED));
            $this->assertTrue($aliceClient->addressBookAddContact($bobClient->getIdentifiedAs()));
            
            // John resolves alice as non-contact
            $aliceResolvedNonContact = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertNotNull($aliceResolvedNonContact);
            $this->assertCount(1, $aliceResolvedNonContact->getInformationFields());
            $this->assertTrue($aliceResolvedNonContact->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertFalse($aliceResolvedNonContact->informationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            $this->assertFalse($aliceResolvedNonContact->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertFalse($aliceResolvedNonContact->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertFalse($aliceResolvedNonContact->informationFieldExists(InformationFieldName::PHONE_NUMBER));
            $this->assertEquals('Alice Smith', $aliceResolvedNonContact->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());

            // Alice resolves john as non-contact
            $johnResolvedNonContact = $aliceClient->resolvePeer($johnClient->getIdentifiedAs());
            $this->assertNotNull($johnResolvedNonContact);
            $this->assertCount(1, $johnResolvedNonContact->getInformationFields());
            $this->assertTrue($johnResolvedNonContact->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertFalse($johnResolvedNonContact->informationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            $this->assertFalse($johnResolvedNonContact->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertFalse($johnResolvedNonContact->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertFalse($johnResolvedNonContact->informationFieldExists(InformationFieldName::PHONE_NUMBER));
            $this->assertEquals('John Doe', $johnResolvedNonContact->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());

            // John adds Alice as a trusted client and so does Alice
            $johnClient->addressBookAddContact($aliceClient->getIdentifiedAs(), ContactRelationshipType::TRUSTED);
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));
            $aliceClient->addressBookAddContact($johnClient->getIdentifiedAs(), ContactRelationshipType::TRUSTED);
            $this->assertTrue($aliceClient->addressBookContactExists($johnClient->getIdentifiedAs()));

            // John resolves alice as contact
            $aliceResolvedContact = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertNotNull($aliceResolvedContact);
            $this->assertCount(4, $aliceResolvedContact->getInformationFields());
            $this->assertTrue($aliceResolvedContact->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals('Alice Smith', $aliceResolvedContact->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertTrue($aliceResolvedContact->informationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            $this->assertEquals('alicesmith@example.com', $aliceResolvedContact->getInformationField(InformationFieldName::EMAIL_ADDRESS)->getValue());
            $this->assertTrue($aliceResolvedContact->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertEquals('Alice', $aliceResolvedContact->getInformationField(InformationFieldName::FIRST_NAME)->getValue());
            $this->assertFalse($aliceResolvedContact->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertTrue($aliceResolvedContact->informationFieldExists(InformationFieldName::PHONE_NUMBER));
            $this->assertEquals('+18193447227', $aliceResolvedContact->getInformationField(InformationFieldName::PHONE_NUMBER)->getValue());

            // Alice resolves john as contact
            $johnResolvedContact = $aliceClient->resolvePeer($johnClient->getIdentifiedAs());
            $this->assertNotNull($johnResolvedContact);
            $this->assertCount(4, $johnResolvedContact->getInformationFields());
            $this->assertTrue($johnResolvedContact->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals('John Doe', $johnResolvedContact->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertTrue($johnResolvedContact->informationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            $this->assertEquals('johndoe@example.com', $johnResolvedContact->getInformationField(InformationFieldName::EMAIL_ADDRESS)->getValue());
            $this->assertTrue($johnResolvedContact->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertEquals('John', $johnResolvedContact->getInformationField(InformationFieldName::FIRST_NAME)->getValue());
            $this->assertFalse($johnResolvedContact->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertTrue($johnResolvedContact->informationFieldExists(InformationFieldName::PHONE_NUMBER));
            $this->assertEquals('+17712579155', $johnResolvedContact->getInformationField(InformationFieldName::PHONE_NUMBER)->getValue());

            // Bob resolves alice as contact
            $aliceBobResolvedContact = $bobClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertNotNull($aliceBobResolvedContact);
            $this->assertCount(2, $aliceBobResolvedContact->getInformationFields());
            $this->assertTrue($aliceBobResolvedContact->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertEquals('Alice Smith', $aliceBobResolvedContact->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertTrue($aliceBobResolvedContact->informationFieldExists(InformationFieldName::EMAIL_ADDRESS));
            $this->assertEquals('alicesmith@example.com', $aliceBobResolvedContact->getInformationField(InformationFieldName::EMAIL_ADDRESS)->getValue());
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookPrivacyUpdateRelationship(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe', PrivacyState::PUBLIC));
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'John', PrivacyState::CONTACTS));
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Doe', PrivacyState::TRUSTED));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith', PrivacyState::PUBLIC));
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::FIRST_NAME, 'Alice', PrivacyState::CONTACTS));
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::LAST_NAME, 'Smith', PrivacyState::TRUSTED));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());

            $this->assertTrue($aliceClient->addressBookAddContact($johnClient->getIdentifiedAs()));
            $this->assertTrue($aliceClient->addressBookContactExists($johnClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));

            $aliceContact = $johnClient->addressBookGetContact($aliceClient->getIdentifiedAs());
            $aliceResolved = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertNotNull($aliceContact);
            $this->assertNotNull($aliceResolved);
            $this->assertEquals(ContactRelationshipType::MUTUAL, $aliceContact->getRelationship());
            $this->assertCount(2, $aliceResolved->getInformationFields());
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertEquals('Alice Smith', $aliceResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertEquals('Alice', $aliceResolved->getInformationField(InformationFieldName::FIRST_NAME)->getValue());

            $johnContact = $aliceClient->addressBookGetContact($johnClient->getIdentifiedAs());
            $johnResolved = $aliceClient->resolvePeer($johnClient->getIdentifiedAs());
            $this->assertNotNull($johnContact);
            $this->assertNotNull($johnResolved);
            $this->assertEquals(ContactRelationshipType::MUTUAL, $johnContact->getRelationship());
            $this->assertCount(2, $johnResolved->getInformationFields());
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertEquals('John Doe', $johnResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertEquals('John', $johnResolved->getInformationField(InformationFieldName::FIRST_NAME)->getValue());

            $this->assertTrue($aliceClient->addressBookUpdateRelationship($johnClient->getIdentifiedAs(), ContactRelationshipType::TRUSTED));
            $this->assertTrue($johnClient->addressBookUpdateRelationship($aliceClient->getIdentifiedAs(), ContactRelationshipType::TRUSTED));

            $aliceContact = $johnClient->addressBookGetContact($aliceClient->getIdentifiedAs());
            $aliceResolved = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertNotNull($aliceContact);
            $this->assertNotNull($aliceResolved);
            $this->assertEquals(ContactRelationshipType::TRUSTED, $aliceContact->getRelationship());
            $this->assertCount(3, $aliceResolved->getInformationFields());
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertTrue($aliceResolved->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertEquals('Alice Smith', $aliceResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertEquals('Alice', $aliceResolved->getInformationField(InformationFieldName::FIRST_NAME)->getValue());
            $this->assertEquals('Smith', $aliceResolved->getInformationField(InformationFieldName::LAST_NAME)->getValue());

            $johnContact = $aliceClient->addressBookGetContact($johnClient->getIdentifiedAs());
            $johnResolved = $aliceClient->resolvePeer($johnClient->getIdentifiedAs());
            $this->assertNotNull($johnContact);
            $this->assertNotNull($johnResolved);
            $this->assertEquals(ContactRelationshipType::TRUSTED, $johnContact->getRelationship());
            $this->assertCount(3, $johnResolved->getInformationFields());
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::DISPLAY_NAME));
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::FIRST_NAME));
            $this->assertTrue($johnResolved->informationFieldExists(InformationFieldName::LAST_NAME));
            $this->assertEquals('John Doe', $johnResolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
            $this->assertEquals('John', $johnResolved->getInformationField(InformationFieldName::FIRST_NAME)->getValue());
            $this->assertEquals('Doe', $johnResolved->getInformationField(InformationFieldName::LAST_NAME)->getValue());
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testDeleteExistingContact(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe', PrivacyState::PUBLIC));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceAddressBookTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith', PrivacyState::PUBLIC));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());

            $this->assertTrue($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));
            $this->assertTrue($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));

            $this->assertTrue($johnClient->addressBookDeleteContact($aliceClient->getIdentifiedAs()));
            $this->assertFalse($johnClient->addressBookContactExists($aliceClient->getIdentifiedAs()));
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testDeleteNonExistentContact(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe', PrivacyState::PUBLIC));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));

            $this->assertFalse($johnClient->addressBookDeleteContact(Helper::generateRandomPeer($johnClient->getIdentifiedAs()->getDomain())));
        }

        /**
         * @throws RpcException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws DatabaseOperationException
         */
        public function testDeleteInvalidContact(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnAddressBookTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe', PrivacyState::PUBLIC));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));

            $this->expectException(RpcException::class);
            $this->expectExceptionCode(StandardError::RPC_INVALID_ARGUMENTS->value);
            $this->assertFalse($johnClient->addressBookDeleteContact('invalid invalid invalid'));
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookAddDuplicateContact(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnDupTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceDupTest');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith');
            $aliceClient->settingsSetPassword('SecretTestingPassword123');

            // First addition should succeed
            $this->assertTrue($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));

            // Second addition should fail
            $this->assertFalse($johnClient->addressBookAddContact($aliceClient->getIdentifiedAs()));
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookUpdateRelationshipToBlocked(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnBlockTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe', PrivacyState::PUBLIC);
            $johnClient->settingsSetPassword('SecretTestingPassword123');

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceBlockTest');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith', PrivacyState::PUBLIC);
            $aliceClient->settingsAddInformationField(InformationFieldName::EMAIL_ADDRESS, 'alice@example.com', PrivacyState::CONTACTS);
            $aliceClient->settingsSetPassword('SecretTestingPassword123');

            $johnClient->addressBookAddContact($aliceClient->getIdentifiedAs());
            $johnClient->addressBookUpdateRelationship($aliceClient->getIdentifiedAs(), ContactRelationshipType::BLOCKED);

            $resolved = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertCount(1, $resolved->getInformationFields(), 'Blocked contacts should return not return anything but public information');
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testInvalidContactAddressFormat(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnInvalidTest');
            $johnClient->settingsSetPassword('SecretTestingPassword123');

            $this->expectException(RpcException::class);
            $johnClient->addressBookAddContact('invalid-email-format');
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testCaseInsensitiveContactAddress(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnCaseTest');
            $this->assertTrue($johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe'));
            $this->assertTrue($johnClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($johnClient->getSessionState()->isAuthenticated());

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceCaseTest');
            $this->assertTrue($aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith'));
            $this->assertTrue($aliceClient->settingsSetPassword('SecretTestingPassword123'));
            $this->assertTrue($aliceClient->getSessionState()->isAuthenticated());
            $aliceAddress = $aliceClient->getIdentifiedAs();
            $mixedCaseAddress = ucfirst(strtolower($aliceAddress->getUsername())).'@'.strtoupper($aliceAddress->getDomain());

            $johnClient->addressBookAddContact($mixedCaseAddress);
            $this->assertTrue($johnClient->addressBookContactExists($aliceAddress), 'Address comparison should be case-insensitive');
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testContactInformationUpdatePropagation(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnUpdateTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceUpdateTest');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith');
            $aliceClient->settingsSetPassword('SecretTestingPassword123');

            $johnClient->addressBookAddContact($aliceClient->getIdentifiedAs());

            // Update Alice's information
            $aliceClient->settingsUpdateInformationField(InformationFieldName::DISPLAY_NAME, 'New Name');

            // Verify John sees the update
            $resolved = $johnClient->resolvePeer($aliceClient->getIdentifiedAs());
            $this->assertEquals('New Name', $resolved->getInformationField(InformationFieldName::DISPLAY_NAME)->getValue());
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testRevokeNonExistentSignature(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnRevokeTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceRevokeTest');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith');
            $aliceClient->settingsSetPassword('SecretTestingPassword123');

            $this->assertFalse($johnClient->addressBookRevokeSignature($aliceClient->getIdentifiedAs(), 'non-existent-uuid'));
        }

        /**
         * @throws DatabaseOperationException
         * @throws ResolutionException
         * @throws CryptographyException
         * @throws RpcException
         */
        public function testAddressBookGetSingleContact(): void
        {
            $johnClient = Helper::generateRandomClient(TEAPOT_DOMAIN, prefix: 'johnGetSingleTest');
            $johnClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'John Doe');
            $johnClient->settingsSetPassword('SecretTestingPassword123');

            $aliceClient = Helper::generateRandomClient(COFFEE_DOMAIN, prefix: 'aliceGetSingleTest');
            $aliceClient->settingsAddInformationField(InformationFieldName::DISPLAY_NAME, 'Alice Smith');
            $aliceClient->settingsSetPassword('SecretTestingPassword123');
            $johnClient->addressBookAddContact($aliceClient->getIdentifiedAs());

            $contact = $johnClient->addressBookGetContact($aliceClient->getIdentifiedAs());
            $this->assertInstanceOf(Contact::class, $contact);
            $this->assertEquals($aliceClient->getIdentifiedAs()->getAddress(), $contact->getAddress()->getAddress());

            $this->expectException(RpcException::class);
            $johnClient->addressBookGetContact('non-existent@coffee.com');
        }
    }