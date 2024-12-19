<?php

    namespace Socialbox\Objects;

    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Interfaces\SerializableInterface;

    class RpcError implements SerializableInterface
    {
        private string $id;
        private StandardError $code;
        private string $error;

        /**
         * Constructs the RPC error object.
         *
         * @param string $id The ID of the RPC request
         * @param StandardError|int $code The error code
         * @param string|null $error The error message
         */
        public function __construct(string $id, StandardError|int $code, ?string $error)
        {
            $this->id = $id;

            if(is_int($code))
            {
                $code = StandardError::tryFrom($code);
                if($code === null)
                {
                    $code = StandardError::UNKNOWN;
                }
            }

            $this->code = $code;

            if($error === null)
            {
                $this->error = $code->getMessage();
            }
            else
            {
                $this->error = $error;
            }

        }

        /**
         * Returns the ID of the RPC request.
         *
         * @return string The ID of the RPC request.
         */
        public function getId(): string
        {
            return $this->id;
        }

        /**
         * Returns the error code.
         *
         * @return StandardError The error code.
         */
        public function getCode(): StandardError
        {
            return $this->code;
        }

        /**
         * Returns the error message.
         *
         * @return string The error message.
         */
        public function getError(): string
        {
            return $this->error;
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
                'code' => $this->code->value,
                'error' => $this->error
            ];
        }

        /**
         * Converts the current object to an RpcException instance.
         *
         * @return RpcException The RpcException generated from the current object.
         */
        public function toRpcException(): RpcException
        {
            return new RpcException($this->error, $this->code->value);
        }

        /**
         * Returns the RPC error object from an array of data.
         *
         * @param array $data The data to construct the RPC error from.
         * @return RpcError The RPC error object.
         */
        public static function fromArray(array $data): RpcError
        {
            return new RpcError($data['id'], $data['code'], $data['error']);
        }
    }