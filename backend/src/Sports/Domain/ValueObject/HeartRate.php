<?php

declare(strict_types=1);

namespace App\Sports\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class HeartRate
{
    #[ORM\Column(type: 'integer')]
    private int $bpm;

    public function __construct(int $bpm)
    {
        if ($bpm < 30 || $bpm > 250) {
            throw new \InvalidArgumentException('Heart rate must be between 30 and 250 bpm');
        }
        $this->bpm = $bpm;
    }

    public function toBpm(): int
    {
        return $this->bpm;
    }

    public function __toString(): string
    {
        return $this->bpm . ' bpm';
    }
}
