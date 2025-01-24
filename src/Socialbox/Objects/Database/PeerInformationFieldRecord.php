<?php

    namespace Socialbox\Objects\Database;

    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Standard\InformationField;
    use Socialbox\Objects\Standard\InformationFieldState;

    class PeerInformationFieldRecord implements SerializableInterface
    {
        private string $peerUuid;
        private InformationFieldName $propertyName;
        private string $propertyValue;
        private PrivacyState $privacyState;

        public function __construct(array $data)
        {
            $this->peerUuid = $data['peer_uuid'];
            $this->propertyName = InformationFieldName::from($data['property_name']);
            $this->propertyValue = $data['property_value'];
            $this->privacyState = PrivacyState::from($data['privacy_state']);
        }

        /**
         * @return string
         */
        public function getPeerUuid(): string
        {
            return $this->peerUuid;
        }

        /**
         * @return InformationFieldName
         */
        public function getPropertyName(): InformationFieldName
        {
            return $this->propertyName;
        }

        /**
         * @return string
         */
        public function getPropertyValue(): string
        {
            return $this->propertyValue;
        }

        /**
         * @return PrivacyState
         */
        public function getPrivacyState(): PrivacyState
        {
            return $this->privacyState;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): PeerInformationFieldRecord
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'peer_uuid' => $this->peerUuid,
                'property_name' => $this->propertyName->value,
                'property_value' => $this->propertyValue,
                'privacy_state' => $this->privacyState->value
            ];
        }

        /**
         * Converts the record to a standard InformationField object
         *
         * @return InformationField
         */
        public function toInformationField(): InformationField
        {
            return new InformationField([
                'name' => $this->propertyName->value,
                'value' => $this->propertyValue
            ]);
        }

        /**
         * Converts the record to a standard InformationFieldState object
         *
         * @return InformationFieldState
         */
        public function toInformationFieldState(): InformationFieldState
        {
            return new InformationFieldState([
                'name' => $this->propertyName->value,
                'value' => $this->propertyValue,
                'privacy_state' => $this->privacyState->value
            ]);
        }
    }