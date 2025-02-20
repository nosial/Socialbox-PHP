<?php

    namespace Socialbox\Objects;

    use InvalidArgumentException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\ReservedUsernames;

    class PeerAddress
    {
        private string $username;
        private string $domain;

        /**
         * Constructs a PeerAddress object from a given username and domain
         *
         * @param string $username The username of the peer
         * @param string $domain The domain of the peer
         */
        public function __construct(string $username, string $domain)
        {
            $this->username = $username;
            $this->domain = $domain;
        }

        /**
         * Constructs a PeerAddress from a full peer address (eg; john@example.com)
         *
         * @param string $address The full address of the peer
         * @return PeerAddress The constructed PeerAddress object
         */
        public static function fromAddress(string $address): PeerAddress
        {
            if(!Validator::validatePeerAddress($address))
            {
                throw new InvalidArgumentException("Invalid peer address: $address");
            }

            $parts = explode('@', $address);
            return new PeerAddress($parts[0], $parts[1]);
        }

        /**
         * Returns the username of the peer
         *
         * @return string
         */
        public function getUsername(): string
        {
            return $this->username;
        }

        /**
         * Returns the domain of the peer
         *
         * @return string
         */
        public function getDomain(): string
        {
            return $this->domain;
        }

        /**
         * Returns whether the peer is the host
         *
         * @return bool True if the peer is the host, false otherwise
         */
        public function isHost(): bool
        {
            return $this->username === ReservedUsernames::HOST->value && $this->domain === Configuration::getInstanceConfiguration()->getDomain();
        }

        /**
         * Determines if the peer is external.
         *
         * @return bool True if the peer is external, false otherwise.
         */
        public function isExternal(): bool
        {
            if($this->isHost())
            {
                return false;
            }

            return $this->domain !== Configuration::getInstanceConfiguration()->getDomain();
        }

        /**
         * Returns whether the peer requires authentication, for example, the anonymous user does not require authentication
         *
         * @return bool True if authentication is required, false otherwise
         */
        public function authenticationRequired(): bool
        {
            return match($this->username)
            {
                ReservedUsernames::ANONYMOUS->value => false,
                default => true
            };
        }

        /**
         * Returns the full address of the peer
         *
         * @return string The full address of the peer
         */
        public function getAddress(): string
        {
            return sprintf("%s@%s", $this->username, $this->domain);
        }

        /**
         * Returns the string representation of the object
         *
         * @return string The string representation of the object
         */
        public function __toString(): string
        {
            return $this->getAddress();
        }
    }