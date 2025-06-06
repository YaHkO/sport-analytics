<?php

declare(strict_types=1);

namespace App\Sports\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Duration
{
    #[ORM\Column(type: 'integer')]
    private int $seconds;

    public function __construct(int $seconds)
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('Duration cannot be negative');
        }
        $this->seconds = $seconds;
    }

    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds);
    }

    public function toSeconds(): int
    {
        return $this->seconds;
    }

    public function toHours(): float
    {
        return $this->seconds / 3600;
    }

    public function toFormattedString(): string
    {
        $hours = floor($this->seconds / 3600);
        $minutes = floor(($this->seconds % 3600) / 60);
        $seconds = $this->seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
