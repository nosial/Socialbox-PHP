<?php

    namespace Socialbox\Objects;

    use InvalidArgumentException;
    use Socialbox\Classes\Logger;
    use Socialbox\Classes\Utilities;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;

    class RpcRequest implements SerializableInterface
    {
        private ?string $id;
        private string $method;
        private ?array $parameters;

        /**
         * Constructs the object from an array of data.
         *
         * @param string|StandardMethods $method The method of the request.
         * @param string|null $id The ID of the request. If 'RANDOM' a random crc32 hash will be used.
         * @param array|null $parameters The parameters of the request.
         */
        public function __construct(string|StandardMethods $method, ?string $id='RANDOM', ?array $parameters=null)
        {
            if($method instanceof StandardMethods)
            {
                $method = $method->value;
            }

            if($id === 'RANDOM')
            {
                $id = Utilities::randomCrc32();;
            }

            $this->method = $method;
            $this->parameters = $parameters;
            $this->id = $id;
        }

        /**
         * Returns the ID of the request.
         *
         * @return string|null The ID of the request.
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Returns the method of the request.
         *
         * @return string The method of the request.
         */
        public function getMethod(): string
        {
            return $this->method;
        }

        /**
         * Returns the parameters of the request.
         *
         * @return array|null The parameters of the request, null if the request is a notification.
         */
        public function getParameters(): ?array
        {
            return $this->parameters;
        }

        /**
         * Checks if the parameter exists within the RPC request
         *
         * @param string $parameter The parameter to check
         * @param bool $strict True if the parameter value cannot be null (or empty), False otherwise.
         * @return bool True if the parameter exists, False otherwise.
         */
        public function containsParameter(string $parameter, bool $strict=true): bool
        {
            if($strict)
            {
                if(!isset($this->parameters[$parameter]))
                {
                    return false;
                }

                if(is_string($this->parameters[$parameter]) && strlen($this->parameters[$parameter]) == 0)
                {
                    return false;
                }

                if(is_array($this->parameters[$parameter]) && count($this->parameters[$parameter]) == 0)
                {
                    return false;
                }

                if(is_null($this->parameters[$parameter]))
                {
                    return false;
                }

                return true;
            }

            return isset($this->parameters[$parameter]);
        }

        /**
         * Returns the parameter value from the RPC request
         *
         * @param string $parameter The parameter name to get
         * @return mixed The parameter value, null if the parameter value is null or not found.
         */
        public function getParameter(string $parameter): mixed
        {
            if(!$this->containsParameter($parameter))
            {
                return null;
            }

            return $this->parameters[$parameter];
        }

        /**
         * Produces a response based off the request, null if the request is a notification
         *
         * @param mixed|null $result
         * @return RpcResponse|null
         */
        public function produceResponse(mixed $result=null): ?RpcResponse
        {
            if($this->id == null)
            {
                return null;
            }

            $valid = false;
            if(is_array($result))
            {
                $valid = true;
            }
            elseif($result instanceof SerializableInterface)
            {
                $valid = true;
            }
            elseif(is_string($result))
            {
                $valid = true;
            }
            elseif(is_bool($result))
            {
                $valid = true;
            }
            elseif(is_int($result))
            {
                $valid = true;
            }
            elseif(is_null($result))
            {
                $valid = true;
            }

            if(!$valid)
            {
                throw new InvalidArgumentException('The \'$result\' property must either be string, boolean, integer, array, null or SerializableInterface');
            }

            Logger::getLogger()->verbose(sprintf('Producing response for request %s', $this->id));
            return new RpcResponse($this->id, $result);
        }

        /**
         * Produces an error response based off the request, null if the request is a notification
         *
         * @param StandardError $error
         * @param string|null $message
         * @return RpcError|null
         */
        public function produceError(StandardError $error, ?string $message=null): ?RpcError
        {
            if($this->id == null)
            {
                return null;
            }

            if($message == null)
            {
                $message = $error->getMessage();
            }

            return new RpcError($this->id, $error, $message);
        }

        /**
         * @param StandardRpcException $e
         * @return RpcError|null
         */
        public function handleStandardException(StandardRpcException $e): ?RpcError
        {
            return $this->produceError($e->getStandardError(), $e->getMessage());
        }

        /**
         * Returns an array representation of the object.
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'method' => $this->method,
                'parameters' => $this->parameters
            ];
        }

        /**
         * Returns the request object from an array of data.
         *
         * @param array $data The data to construct the object from.
         * @return RpcRequest The request object.
         */
        public static function fromArray(array $data): RpcRequest
        {
            return new RpcRequest($data['method'], $data['id'] ?? null, $data['parameters'] ?? null);
        }
    }