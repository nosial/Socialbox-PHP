<?php

namespace Socialbox\Objects\Standard;

use Socialbox\Interfaces\SerializableInterface;

class ImageCaptcha implements SerializableInterface
{
    private int $expires;
    private string $image;

    public function __construct(array $data)
    {
        $this->expires = $data['expires'];
        $this->image = $data['image'];
    }

    /**
     * @return int
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data): object
    {
        return new self($data);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'expires' => $this->expires,
            'image' => $this->image
        ];
    }
}