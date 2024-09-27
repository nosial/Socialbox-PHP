<?php

namespace Socialbox\Objects;

use Socialbox\Enums\StandardError;
use Socialbox\Interfaces\SerializableInterface;

class RpcError implements SerializableInterface
{
    private string $id;
    private string $error;
    private StandardError $code;

    /**
     * Constructs the RPC error object.
     *
     * @param string $id The ID of the RPC request
     * @param StandardError $code The error code
     * @param string $error The error message
     */
    public function __construct(string $id, StandardError $code, ?string $error)
    {
        $this->id = $id;
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
     * Returns the error message.
     *
     * @return string The error message.
     */
    public function getError(): string
    {
        return $this->error;
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
     * Returns an array representation of the object.
     *
     * @return array The array representation of the object.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'error' => $this->error,
            'code' => $this->code->value
        ];
    }

    /**
     * Returns the RPC error object from an array of data.
     *
     * @param array $data The data to construct the RPC error from.
     * @return RpcError The RPC error object.
     */
    public static function fromArray(array $data): RpcError
    {
        $errorCode = StandardError::tryFrom($data['code']);

        if($errorCode == null)
        {
            $errorCode = StandardError::UNKNOWN;
        }

        return new RpcError($data['id'], $data['error'], $errorCode);
    }
}