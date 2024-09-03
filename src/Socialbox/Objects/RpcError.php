<?php

namespace Socialbox\Objects;

use Socialbox\Interfaces\SerializableInterface;

class RpcError implements SerializableInterface
{
    private string $id;
    private string $error;
    private int $code;

    /**
     * Constructs the RPC error object.
     *
     * @param string $id The ID of the RPC request
     * @param string $error The error message
     * @param int $code The error code
     */
    public function __construct(string $id, string $error, int $code)
    {
        $this->id = $id;
        $this->error = $error;
        $this->code = $code;
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
     * @return int The error code.
     */
    public function getCode(): int
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
            'code' => $this->code
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
        return new RpcError($data['id'], $data['error'], $data['code']);
    }
}