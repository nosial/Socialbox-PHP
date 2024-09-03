<?php

namespace Socialbox\Objects;

use Socialbox\Abstracts\Entity;
use Socialbox\Interfaces\SerializableInterface;

class RpcRequest implements SerializableInterface
{
    protected ?string $id;
    protected string $method;
    protected ?array $parameters;

    /**
     * Constructs the object from an array of data.
     *
     * @param array $data The data to construct the object from.
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->method = $data['method'];
        $this->parameters = $data['parameters'] ?? null;
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
     * @return array|null The parameters of the request.
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
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
        return static($data);
    }
}