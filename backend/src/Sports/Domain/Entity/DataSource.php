<?php

declare(strict_types=1);

namespace App\Sports\Domain\Entity;

enum DataSource: string
{
    case STRAVA = 'strava';
    case GARMIN = 'garmin';
    case MANUAL = 'manual';

    public function getDisplayName(): string
    {
        return match($this) {
            self::STRAVA => 'Strava',
            self::GARMIN => 'Garmin Connect',
            self::MANUAL => 'Manuel',
        };
    }
}
