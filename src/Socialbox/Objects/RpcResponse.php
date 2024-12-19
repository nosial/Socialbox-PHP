<?php

    namespace Socialbox\Objects;

    use Socialbox\Interfaces\SerializableInterface;

    class RpcResponse implements SerializableInterface
    {
        private string $id;
        private mixed $result;

        /**
         * Constructs the response object.
         *
         * @param string $id The ID of the response.
         * @param mixed|null $result The result of the response.
         */
        public function __construct(string $id, mixed $result)
        {
            $this->id = $id;
            $this->result = $result;
        }

        /**
         * Returns the ID of the response.
         *
         * @return string The ID of the response.
         */
        public function getId(): string
        {
            return $this->id;
        }

        /**
         * Returns the result of the response.
         *
         * @return mixed|null The result of the response.
         */
        public function getResult(): mixed
        {
            return $this->result;
        }

        /**
         * Converts the given data to an array.
         *
         * @param mixed $data The data to be converted. This can be an instance of SerializableInterface, an array, or a scalar value.
         * @return mixed The converted data as an array if applicable, or the original data.
         */
        private function convertToArray(mixed $data): mixed
        {
            // If the data is an instance of SerializableInterface, call toArray on it
            if ($data instanceof SerializableInterface)
            {
                return $data->toArray();
            }

            return $data;
        }

        /**
         * Returns an array representation of the object.
         *
         * @return array The array representation of the object.
         */
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'result' => $this->convertToArray($this->result)
            ];
        }

        /**
         * Returns the response object from an array of data.
         *
         * @param array $data The data to construct the response from.
         * @return RpcResponse The response object.
         */
        public static function fromArray(array $data): RpcResponse
        {
            return new RpcResponse($data['id'], $data['result']);
        }
    }