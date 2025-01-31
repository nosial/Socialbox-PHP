<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Standard\KnownSigningKey;

    class ContactKnownKeyRecord implements SerializableInterface
    {
        private string $contactUuid;
        private string $signatureUuid;
        private string $signatureName;
        private string $signatureKey;
        private ?DateTime $expires;
        private DateTime $created;
        private DateTime $trustedOn;

        /**
         * Constructs a new instance with the provided parameters.
         *
         * @param array $data The array of data to use for the object.
         * @throws \DateMalformedStringException If the date string is malformed.
         */
        public function __construct(array $data)
        {
            $this->contactUuid = $data['contact_uuid'];
            $this->signatureUuid = $data['signature_uuid'];
            $this->signatureName = $data['signature_name'];
            $this->signatureKey = $data['signature_key'];

            if(!isset($data['expires']))
            {
                $this->expires = null;
            }
            else
            {
                if(is_string($data['expires']))
                {
                    $this->expires = new DateTime($data['expires']);
                }
                elseif(is_int($data['expires']))
                {
                    $this->expires = (new DateTime())->setTimestamp($data['expires']);
                }
                elseif($data['expires'] instanceof DateTime)
                {
                    $this->expires = $data['expires'];
                }
                else
                {
                    throw new InvalidArgumentException('Invalid expires property, got type ' . gettype($data['expires']));
                }
            }

            if(!isset($data['created']))
            {
                throw new InvalidArgumentException('Missing created property');
            }
            else
            {
                if(is_string($data['created']))
                {
                    $this->created = new DateTime($data['created']);
                }
                elseif(is_int($data['created']))
                {
                    $this->created = (new DateTime())->setTimestamp($data['created']);
                }
                elseif($data['created'] instanceof DateTime)
                {
                    $this->created = $data['created'];
                }
                else
                {
                    throw new InvalidArgumentException('Invalid created property, got type ' . gettype($data['created']));
                }
            }

            if(!isset($data['trusted_on']))
            {
                throw new InvalidArgumentException('Missing trusted_on property');
            }
            else
            {
                if(is_string($data['trusted_on']))
                {
                    $this->trustedOn = new DateTime($data['trusted_on']);
                }
                elseif(is_int($data['trusted_on']))
                {
                    $this->trustedOn = (new DateTime())->setTimestamp($data['trusted_on']);
                }
                elseif($data['trusted_on'] instanceof DateTime)
                {
                    $this->trustedOn = $data['trusted_on'];
                }
                else
                {
                    throw new InvalidArgumentException('Invalid trusted_on property, got type ' . gettype($data['trusted_on']));
                }
            }
        }

        /**
         * Gets the contact UUID.
         *
         * @return string The contact UUID.
         */
        public function getContactUuid(): string
        {
            return $this->contactUuid;
        }

        /**
         * Gets the signature UUID.
         *
         * @return string The signature UUID.
         */
        public function getSignatureUuid(): string
        {
            return $this->signatureUuid;
        }

        /**
         * Gets the signature name.
         *
         * @return string The signature name.
         */
        public function getSignatureName(): string
        {
            return $this->signatureName;
        }

        /**
         * Gets the signature key.
         *
         * @return string The signature key.
         */
        public function getSignatureKey(): string
        {
            return $this->signatureKey;
        }

        /**
         * Gets the expiration date.
         *
         * @return DateTime|null The expiration date.
         */
        public function getExpires(): ?DateTime
        {
            return $this->expires;
        }

        /**
         * Gets the creation date.
         *
         * @return DateTime The creation date.
         */
        public function getCreated(): DateTime
        {
            return $this->created;
        }

        /**
         * Gets the trusted on date.
         *
         * @return DateTime The trusted on date.
         */
        public function getTrustedOn(): DateTime
        {
            return $this->trustedOn;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ContactKnownKeyRecord
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toStandard(): KnownSigningKey
        {
            return new KnownSigningKey([
                'uuid' => $this->signatureUuid,
                'name' => $this->signatureName,
                'public_key' => $this->signatureKey,
                'expires' => $this->expires?->getTimestamp(),
                'created' => $this->created->getTimestamp(),
                'trusted_on' => $this->trustedOn->getTimestamp()
            ]);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'contact_uuid' => $this->contactUuid,
                'signature_uuid' => $this->signatureUuid,
                'signature_name' => $this->signatureName,
                'signature_key' => $this->signatureKey,
                'expires' => $this->expires?->getTimestamp(),
                'created' => $this->created->getTimestamp(),
                'trusted_on' => $this->trustedOn->getTimestamp()
            ];
        }
    }