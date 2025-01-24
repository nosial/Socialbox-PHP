<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Interfaces\SerializableInterface;

    class InformationFieldState implements SerializableInterface
    {
        private InformationFieldName $name;
        private string $value;
        private PrivacyState $privacyState;

        public function __construct(array $data)
        {
            $this->name = InformationFieldName::from($data['name']);
            $this->value = $data['value'];
            $this->privacyState = PrivacyState::from($data['privacy_state']);
        }

        /**
         * @return InformationFieldName
         */
        public function getName(): InformationFieldName
        {
            return $this->name;
        }

        /**
         * @return string
         */
        public function getValue(): string
        {
            return $this->value;
        }

        public function getPrivacyState(): PrivacyState
        {
            return $this->privacyState;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): InformationFieldState
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name->value,
                'value' => $this->value,
                'privacy_state' => $this->privacyState->value
            ];
        }

        /**
         * Converts the object to an InformationField instance.
         *
         * @return InformationField Returns the converted InformationField instance.
         */
        public function toInformationField(): InformationField
        {
            return new InformationField([
                'name' => $this->name->value,
                'value' => $this->value,
            ]);
        }
    }