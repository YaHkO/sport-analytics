<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Id
{
    private UuidInterface $value;

    public function __construct(string $value = null)
    {
        $this->value = $value ? Uuid::fromString($value) : Uuid::uuid4();
    }

    public static function generate(): self
    {
        return new self();
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function equals(Id $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
