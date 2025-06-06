<?php

declare(strict_types=1);

namespace App\Sports\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Speed
{
    #[ORM\Column(name: 'meters_per_second', type: 'float')]
    private float $metersPerSecond;

    public function __construct(float $metersPerSecond)
    {
        if ($metersPerSecond < 0) {
            throw new \InvalidArgumentException('Speed cannot be negative');
        }
        $this->metersPerSecond = $metersPerSecond;
    }

    public static function fromMetersPerSecond(float $metersPerSecond): self
    {
        return new self($metersPerSecond);
    }

    public function toMetersPerSecond(): float
    {
        return $this->metersPerSecond;
    }

    public function toKmPerHour(): float
    {
        return $this->metersPerSecond * 3.6;
    }
}
