<?php

declare(strict_types=1);

namespace App\Sports\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Distance
{
    #[ORM\Column(type: 'float')]
    private float $meters;

    public function __construct(float $meters)
    {
        if ($meters < 0) {
            throw new \InvalidArgumentException('Distance cannot be negative');
        }
        $this->meters = $meters;
    }

    public static function fromKilometers(float $kilometers): self
    {
        return new self($kilometers * 1000);
    }

    public static function fromMeters(float $meters): self
    {
        return new self($meters);
    }

    public function toMeters(): float
    {
        return $this->meters;
    }

    public function toKilometers(): float
    {
        return $this->meters / 1000;
    }

    public function __toString(): string
    {
        if ($this->meters >= 1000) {
            return number_format($this->toKilometers(), 2) . ' km';
        }
        return number_format($this->meters, 0) . ' m';
    }
}
