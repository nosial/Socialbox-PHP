<?php

namespace Socialbox\Objects;

class RpcResponse
{
    private string $id;
    private ?object $result;

    /**
     * Constructs the response object.
     *
     * @param string $id The ID of the response.
     * @param object|null $result The result of the response.
     */
    public function __construct(string $id, ?object $result)
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
     * @return object|null The result of the response.
     */
    public function getResult(): ?object
    {
        return $this->result;
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
            'result' => $this->result->toArray()
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