<?php

    /** @noinspection PhpUnused */

    namespace Socialbox;

    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\RpcClient;
    use Socialbox\Classes\Utilities;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\ExportedSession;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\InformationField;
    use Socialbox\Objects\Standard\Peer;
    use Socialbox\Objects\Standard\ServerDocument;
    use Socialbox\Objects\Standard\SessionState;

    class SocialClient extends RpcClient
    {
        /**
         * Constructs the object from an array of data.
         *
         * @param string|PeerAddress $identifiedAs The address of the peer to connect to.
         * @param string|null $server Optional. The domain of the server to connect to if different from the identified
         * @param ExportedSession|null $exportedSession Optional. The exported session to use for communication.
         * @throws CryptographyException If the public key is invalid.
         * @throws DatabaseOperationException If the database operation fails.
         * @throws ResolutionException If the domain cannot be resolved.
         * @throws RpcException If the RPC request fails.
         */
        public function __construct(string|PeerAddress $identifiedAs, ?string $server=null, ?ExportedSession $exportedSession=null)
        {
            parent::__construct($identifiedAs, $server, $exportedSession);
        }

        /**
         * Sends a ping request to the server and checks the response.
         *
         * @return true Returns true if the ping request succeeds.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function ping(): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::PING, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the current state of the session from the server.
         *
         * @return SessionState Returns an instance of SessionState representing the session's current state.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getSessionState(): SessionState
        {
            return SessionState::fromArray($this->sendRequest(
                new RpcRequest(StandardMethods::GET_SESSION_STATE, Utilities::randomCrc32())
            )->getResponse()->getResult());
        }

        /**
         * Retrieves the list of allowed methods, these are the methods that can be called by the client.
         *
         * @return array The allowed methods returned from the RPC request.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getAllowedMethods(): array
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::GET_ALLOWED_METHODS, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Fetches the privacy policy document by sending a remote procedure call request.
         *
         * @return ServerDocument The privacy policy document retrieved from the server.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getPrivacyPolicy(): ServerDocument
        {
            return ServerDocument::fromArray($this->sendRequest(
                new RpcRequest(StandardMethods::GET_PRIVACY_POLICY, Utilities::randomCrc32())
            )->getResponse()->getResult());
        }

        /**
         * Accepts the privacy policy by sending a request to the server.
         *
         * @return true Returns true if the privacy policy is successfully accepted.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function acceptPrivacyPolicy(): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::ACCEPT_PRIVACY_POLICY, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the terms of service document by sending a remote procedure call request.
         *
         * @return ServerDocument The terms of service document retrieved from the server.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getTermsOfService(): ServerDocument
        {
            return ServerDocument::fromArray($this->sendRequest(
                new RpcRequest(StandardMethods::GET_TERMS_OF_SERVICE, Utilities::randomCrc32())
            )->getResponse()->getResult());
        }

        /**
         * Sends a request to accept the terms of service and verifies the response.
         *
         * @return true Returns true if the terms of service are successfully accepted.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function acceptTermsOfService(): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::ACCEPT_TERMS_OF_SERVICE, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Fetches the community guidelines document from the server by sending a remote procedure call request.
         *
         * @return ServerDocument The community guidelines document retrieved from the server.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getCommunityGuidelines(): ServerDocument
        {
            return ServerDocument::fromArray($this->sendRequest(
                new RpcRequest(StandardMethods::GET_COMMUNITY_GUIDELINES, Utilities::randomCrc32())
            )->getResponse()->getResult());
        }

        /**
         * Sends a request to accept the community guidelines via a remote procedure call.
         *
         * @return true Indicates that the community guidelines have been successfully accepted.
         * @throws RpcException Thrown if the RPC request encounters an error.
         */
        public function acceptCommunityGuidelines(): true
        {
            return $this->sendRequest(
                new RpcRequest(StandardMethods::ACCEPT_COMMUNITY_GUIDELINES, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Sends a verification email to the specified email address by making a remote procedure call request.
         *
         * @param string $emailAddress The email address to which the verification email will be sent.
         * @return true Indicates the successful initiation of the verification process.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationEmail(string $emailAddress): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_EMAIL, Utilities::randomCrc32(), [
                    'email_address' => $emailAddress
                ])
            )->getResponse()->getResult();
        }

        /**
         * Confirms a verification process using an email verification code by sending a remote procedure call request.
         *
         * @param string $verificationCode The verification code to validate the email.
         * @return true The result indicating the successful processing of the verification.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationAnswerEmail(string $verificationCode): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_ANSWER_EMAIL, Utilities::randomCrc32(), [
                    'verification_code' => $verificationCode
                ])
            )->getResponse()->getResult();
        }

        /**
         * Sends a verification SMS to the specified phone number by initiating a remote procedure call.
         *
         * @param string $phoneNumber The phone number to which the verification SMS should be sent.
         * @return true True if the SMS was sent successfully.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationSms(string $phoneNumber): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_SMS, Utilities::randomCrc32(), [
                    'phone_number' => $phoneNumber
                ])
            )->getResponse()->getResult();
        }

        /**
         * Sends a verification SMS answer by providing the verification code through a remote procedure call request.
         *
         * @param string $verificationCode The verification code to be sent for completing the SMS verification process.
         * @return true Returns true if the verification is successfully processed.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationAnswerSms(string $verificationCode): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_ANSWER_SMS, Utilities::randomCrc32(), [
                    'verification_code' => $verificationCode
                ])
            )->getResponse()->getResult();
        }

        /**
         * Initiates a phone verification process by sending a remote procedure call request.
         *
         * @param string $phoneNumber The phone number to be verified.
         * @return bool True if the phone verification request was successful.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationPhone(string $phoneNumber): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_PHONE_CALL, Utilities::randomCrc32(), [
                    'phone_number' => $phoneNumber
                ])
            )->getResponse()->getResult();
        }

        /**
         * Answers a verification phone call by sending a remote procedure call request with the provided verification code.
         *
         * @param string $verificationCode The verification code to authenticate the phone call.
         * @return true Returns true if the verification phone call was successfully answered.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationAnswerPhone(string $verificationCode): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_ANSWER_PHONE_CALL, Utilities::randomCrc32(), [
                    'verification_code' => $verificationCode
                ])
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the image captcha for verification purposes by sending a remote procedure call request.
         *
         * @return string The result of the image captcha request.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationGetImageCaptcha(): string
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_GET_IMAGE_CAPTCHA, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Submits the answer for an image captcha verification by sending a remote procedure call request.
         *
         * @param string $verificationCode The code provided as the answer to the image captcha.
         * @return true Returns true if the captcha answer is successfully verified.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationAnswerImageCaptcha(string $verificationCode): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_ANSWER_IMAGE_CAPTCHA, Utilities::randomCrc32(), [
                    'verification_code' => $verificationCode
                ])
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the text captcha verification response.
         *
         * @return string The result of the text captcha verification request.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationGetTextCaptcha(): string
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_GET_TEXT_CAPTCHA, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Sends a request to answer a text-based captcha for verification purposes.
         *
         * @param string $verificationCode The code provided to answer the captcha.
         * @return true Returns true if the captcha answer was successfully processed.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationAnswerTextCaptcha(string $verificationCode): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_ANSWER_TEXT_CAPTCHA, Utilities::randomCrc32(), [
                    'verification_code' => $verificationCode
                ])
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the external URL for verification purposes by sending a remote procedure call request.
         *
         * @return string The result of the verification URL request.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationGetExternalUrl(): string
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_GET_EXTERNAL_URL, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Sends a verification code to answer an external URL for verification purposes.
         *
         * @param string $verificationCode The verification code to be sent.
         * @return true The result of the verification operation.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationAnswerExternalUrl(string $verificationCode): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_ANSWER_EXTERNAL_URL, Utilities::randomCrc32(), [
                    'verification_code' => $verificationCode
                ])
            )->getResponse()->getResult();
        }

        /**
         * Authenticates a password by sending a remote procedure call request with an optional hashing operation.
         *
         * @param string $password The password to authenticate.
         * @param bool $hash Indicates whether the password should be hashed using SHA-512 before authentication.
         * @return bool The result of the password authentication request.
         * @throws CryptographyException Thrown if the password hash is invalid.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationPasswordAuthentication(string $password, bool $hash=true): bool
        {
            if($hash)
            {
                $password = hash('sha512', $password);
            }
            elseif(!Cryptography::validateSha512($password))
            {
                throw new CryptographyException('Invalid SHA-512 hash provided');
            }

            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_PASSWORD_AUTHENTICATION, Utilities::randomCrc32(), [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }

        /**
         * Authenticates an OTP code for verification purposes
         *
         * @param string $code The OTP code to be authenticated.
         * @return bool True if the OTP authentication is successful, otherwise false.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function verificationOtpAuthentication(string $code): bool
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::VERIFICATION_OTP_AUTHENTICATION, Utilities::randomCrc32(), [
                    'code' => $code
                ])
            )->getResponse()->getResult();
        }

        /**
         * Sets a new password for settings with optional hashing.
         *
         * @param string $password The password to be set. If hashing is enabled, the password will be hashed before being sent.
         * @param bool $hash Optional. Determines whether the password should be hashed. Default is true. If false, the input is expected to be hashed using sha512.
         * @return true Returns true if the password is successfully set.
         * @throws CryptographyException Thrown if the password hash is invalid.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsSetPassword(string $password, bool $hash=true): true
        {
            if($hash)
            {
                $password = Cryptography::hashPassword($password);
            }
            elseif(!Cryptography::validatePasswordHash($password))
            {
                throw new CryptographyException('Invalid password hash provided');
            }

            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_SET_PASSWORD, Utilities::randomCrc32(), [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }

        /**
         * Deletes the user's password settings by sending a remote procedure call request.
         *
         * @param string $password The password to be deleted.
         * @return true Indicates successful deletion of the password.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsDeletePassword(string $password): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_DELETE_PASSWORD, Utilities::randomCrc32(), [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }

        /**
         * Updates the user's password by sending a remote procedure call request.
         *
         * @param string $password The new password to be set.
         * @param string $existingPassword The current password for authentication.
         * @return bool True if the password was successfully updated, false otherwise.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsUpdatePassword(string $password, string $existingPassword): bool
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_UPDATE_PASSWORD, Utilities::randomCrc32(), [
                    'password' => $password,
                    'existing_password' => $existingPassword
                ])
            )->getResponse()->getResult();
        }

        /**
         * Updates the OTP setting by sending a remote procedure call request with the provided OTP.
         *
         * @param string $otp The OTP to be set. If hashing is enabled, it will be hashed using SHA-512.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsSetOtp(string $otp, bool $hash=true): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_SET_OTP, Utilities::randomCrc32(), [
                    'otp' => $hash ? hash('sha512', $otp) : $otp
                ])
            )->getResponse()->getResult();
        }

        /**
         * Deletes the one-time password (OTP) settings by sending a remote procedure call request.
         *
         * @param string|null $password The password to authenticate the request. If provided, it will be hashed using SHA-512 if $hash is true.
         * @param bool $hash Indicates whether to hash the password before sending the request. Defaults to true.
         * @return bool True if the OTP settings were successfully deleted, false otherwise.
         * @throws CryptographyException Thrown if the password hash is invalid.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsDeleteOtp(?string $password=null, bool $hash=true): bool
        {
            if($hash && $password !== null)
            {
                $password = hash('sha512', $password);
            }
            elseif($password !== null && !Cryptography::validateSha512($password))
            {
                throw new CryptographyException('Invalid SHA-512 hash provided');
            }

            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_DELETE_OTP, Utilities::randomCrc32(), [
                    'password' => $password
                ])
            )->getResponse()->getResult();
        }

        /**
         * Updates the user's OTP settings by sending a remote procedure call request.
         *
         * @param InformationFieldName $field The field to be updated.
         * @param string $value The value to be set.
         * @param PrivacyState|null $privacy The privacy state to be set. Default is null.
         * @return bool True if the OTP was successfully updated, false otherwise.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsAddInformationField(InformationFieldName $field, string $value, ?PrivacyState $privacy=null): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_ADD_INFORMATION_FIELD, Utilities::randomCrc32(), [
                    'field' => $field->value,
                    'value' => $value,
                    'privacy' => $privacy?->value
                ]),
            )->getResponse()->getResult();
        }

        /**
         * Deletes an information field by sending a remote procedure call request.
         *
         * @param InformationField $field The field to be deleted.
         * @return bool True if the field was successfully deleted, false otherwise.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsDeleteInformationField(InformationField $field): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_DELETE_INFORMATION_FIELD, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Updates an information field by sending a remote procedure call request.
         *
         * @param InformationField $field The field to be updated.
         * @param string $value The value to be set.
         * @return bool True if the field was successfully updated, false otherwise.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsUpdateInformationField(InformationField $field, string $value): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_UPDATE_INFORMATION_FIELD, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Updates the privacy of an information field by sending a remote procedure call request.
         *
         * @param InformationField $field The field to be updated.
         * @param PrivacyState $privacy The privacy state to be set.
         * @return bool True if the privacy was successfully updated, false otherwise.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function settingsUpdateInformationPrivacy(InformationField $field, PrivacyState $privacy): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::SETTINGS_UPDATE_INFORMATION_PRIVACY, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Authenticates the user by sending a remote procedure call request.
         * Only applicable for server to server communication, this is the first method to call
         * after connecting to the server.
         *
         * @return true Returns true if the authentication is successful.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function authenticate(): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest(StandardMethods::AUTHENTICATE, Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Resolves a peer by its address or a PeerAddress instance through a remote procedure call.
         *
         * @param string|PeerAddress $peerAddress The peer address as a string or an instance of PeerAddress.
         * @return Peer The resolved peer object.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function resolvePeer(string|PeerAddress $peerAddress, null|string|PeerAddress $identifiedAs=null): Peer
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }

            if($identifiedAs instanceof PeerAddress)
            {
                $identifiedAs = $identifiedAs->getAddress();
            }

            return Peer::fromArray($this->sendRequest(
                new RpcRequest(StandardMethods::RESOLVE_PEER, Utilities::randomCrc32(), [
                    'peer' => $peerAddress
                ]), true, $identifiedAs
            )->getResponse()->getResult());
        }
    }