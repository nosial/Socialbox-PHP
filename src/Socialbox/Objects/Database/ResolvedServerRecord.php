<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\DnsRecord;

    class ResolvedServerRecord implements SerializableInterface
    {
        private string $domain;
        private string $endpoint;
        private string $publicKey;
        private DateTime $expires;
        private DateTime $updated;

        /**
         * Constructs a new instance of the class.
         *
         * @param array $data An associative array containing the domain, endpoint, public_key, and updated values.
         * @throws \DateMalformedStringException
         */
        public function __construct(array $data)
        {
            $this->domain = (string)$data['domain'];
            $this->endpoint = (string)$data['endpoint'];
            $this->publicKey = (string)$data['public_key'];

            if(is_null($data['expires']))
            {
                $this->expires = new DateTime();
            }
            elseif (is_int($data['expires']))
            {
                $this->expires = (new DateTime())->setTimestamp($data['expires']);
            }
            elseif (is_string($data['expires']))
            {
                $this->expires = new DateTime($data['expires']);
            }
            else
            {
                $this->expires = $data['expires'];
            }

            if(is_null($data['updated']))
            {
                $this->updated = new DateTime();
            }
            elseif (is_int($data['updated']))
            {
                $this->updated = (new DateTime())->setTimestamp($data['updated']);
            }
            elseif (is_string($data['updated']))
            {
                $this->updated = new DateTime($data['updated']);
            }
            else
            {
                $this->updated = $data['updated'];
            }
        }

        /**
         * Retrieves the domain value.
         *
         * @return string The domain as a string.
         */
        public function getDomain(): string
        {
            return $this->domain;
        }

        /**
         * Retrieves the configured endpoint.
         *
         * @return string The endpoint as a string.
         */
        public function getEndpoint(): string
        {
            return $this->endpoint;
        }

        /**
         * Retrieves the public key.
         *
         * @return string The public key as a string.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the expiration timestamp.
         *
         * @return DateTime The DateTime object representing the expiration time.
         */
        public function getExpires(): DateTime
        {
            return $this->expires;
        }

        /**
         * Retrieves the timestamp of the last update.
         *
         * @return DateTime The DateTime object representing the last update time.
         */
        public function getUpdated(): DateTime
        {
            return $this->updated;
        }

        /**
         * Fetches the DNS record based on the provided endpoint, public key, and expiration time.
         *
         * @return DnsRecord An instance of the DnsRecord containing the endpoint, public key, and expiration timestamp.
         */
        public function getDnsRecord(): DnsRecord
        {
            return new DnsRecord($this->endpoint, $this->publicKey, $this->expires->getTimestamp());
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): object
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'domain' => $this->domain,
                'endpoint' => $this->endpoint,
                'public_key' => $this->publicKey,
                'updated' => $this->updated->format('Y-m-d H:i:s')
            ];
        }
    }