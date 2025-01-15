<?php

    namespace Socialbox\Objects\Standard;

    use InvalidArgumentException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;

    class Peer implements SerializableInterface
    {
        private PeerAddress $address;
        private string $displayName;
        private array $flags;
        private int $registered;

        /**
         * Constructor method.
         *
         * @param array $data An associative array containing the keys:
         *                    - 'address': A string or an instance of PeerAddress. Used to set the address property.
         *                    - 'display_name': The display name for the entity.
         *                    - 'flags': Flags associated with the entity.
         *                    - 'registered': Registration status or date for the entity.
         *
         * @return void
         * @throws InvalidArgumentException If the 'address' value is neither a string nor an instance of PeerAddress.
         */
        public function __construct(array $data)
        {
            // TODO: Bug:  PHP message: PHP Warning:  Undefined array key "address" in /var/ncc/packages/net.nosial.socialbox=1.0.0/bin/src/Socialbox/Objects/Standard/Peer.php on line 28
            if(is_string($data['address']))
            {
                $this->address = PeerAddress::fromAddress($data['address']);
            }
            elseif($data['address'] instanceof PeerAddress)
            {
                $this->address = $data['address'];
            }
            else
            {
                throw new InvalidArgumentException('Invalid address value');
            }

            $this->displayName = $data['display_name'];
            $this->flags = $data['flags'];
            $this->registered = $data['registered'];
        }

        /**
         * Retrieves the address property.
         *
         * @return PeerAddress The address associated with the instance.
         */
        public function getAddress(): PeerAddress
        {
            return $this->address;
        }

        /**
         * Retrieves the display name of the entity.
         *
         * @return string The display name associated with the entity.
         */
        public function getDisplayName(): string
        {
            return $this->displayName;
        }

        /**
         * Retrieves the flags associated with the entity.
         *
         * @return array An array containing the flags.
         */
        public function getFlags(): array
        {
            return $this->flags;
        }

        /**
         * Retrieves the registered value.
         *
         * @return int The registered property value.
         */
        public function getRegistered(): int
        {
            return $this->registered;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Peer
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'address' => $this->address->getAddress(),
                'display_name' => $this->displayName,
                'flags' => $this->flags,
                'registered' => $this->registered
            ];
        }
    }