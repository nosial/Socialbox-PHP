<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Interfaces\SerializableInterface;

    class InformationField implements SerializableInterface
    {
        private InformationFieldName $name;
        private string $value;

        public function __construct(array $data)
        {
            $this->name = InformationFieldName::from($data['name']);
            $this->value = $data['value'];
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

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): InformationField
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name->getValue(),
                'value' => $this->value,
            ];
        }
    }