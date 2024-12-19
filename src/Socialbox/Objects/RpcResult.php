<?php

    namespace Socialbox\Objects;

    class RpcResult
    {
        private ?RpcResponse $response;
        private ?RpcError $error;

        /**
         * Constructor for initializing the instance with a response or error.
         *
         * @param RpcResponse|RpcError|array $response An instance of RpcResponse, RpcError, or an associative array
         *                                             containing error or result data to initialize the class.
         * @return void
         */
        public function __construct(RpcResponse|RpcError|array $response)
        {
            if(is_array($response))
            {
                if(isset($response['error']) && isset($response['code']))
                {
                    $response = RpcError::fromArray($response);
                }
                elseif(isset($response['result']))
                {
                    $response = RpcResponse::fromArray($response);
                }
                else
                {
                    $response = null;
                }
            }

            if($response === null)
            {
                $this->error = null;
                $this->response = null;
                return;
            }

            if($response instanceof RpcResponse)
            {
                $this->response = $response;
                $this->error = null;
                return;
            }

            if($response instanceof RpcError)
            {
                $this->error = $response;
                $this->response = null;
            }
        }

        /**
         * Checks whether the operation was successful.
         *
         * @return bool True if there is no error, otherwise false.
         */
        public function isSuccess(): bool
        {
            return $this->error === null;
        }

        /**
         * Checks if the instance contains no error and no response.
         *
         * @return bool True if both error and response are null, otherwise false.
         */
        public function isEmpty(): bool
        {
            return $this->error === null && $this->response === null;
        }

        /**
         * Retrieves the error associated with the instance, if any.
         *
         * @return RpcError|null The error object if an error exists, or null if no error is present.
         */
        public function getError(): ?RpcError
        {
            return $this->error;
        }

        /**
         * Retrieves the RPC response if available.
         *
         * @return RpcResponse|null The response object if set, or null if no response is present.
         */
        public function getResponse(): ?RpcResponse
        {
            return $this->response;
        }
    }