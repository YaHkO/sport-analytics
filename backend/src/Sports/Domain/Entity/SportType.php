<?php

declare(strict_types=1);

namespace App\Sports\Domain\Entity;

enum SportType: string
{
    case RUNNING = 'running';
    case CYCLING = 'cycling';
    case SWIMMING = 'swimming';
    case WALKING = 'walking';
    case HIKING = 'hiking';
    case STRENGTH = 'strength';
    case YOGA = 'yoga';
    case WORKOUT = 'workout';
    case OTHER = 'other';

    public function isEndurance(): bool
    {
        return match($this) {
            self::RUNNING, self::CYCLING, self::SWIMMING, self::HIKING => true,
            default => false,
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::RUNNING => '🏃‍♂️',
            self::CYCLING => '🚴‍♂️',
            self::SWIMMING => '🏊‍♂️',
            self::WALKING => '🚶‍♂️',
            self::HIKING => '🥾',
            self::STRENGTH => '💪',
            self::YOGA => '🧘‍♀️',
            self::WORKOUT => '🏋️‍♂️',
            self::OTHER => '🏃‍♂️',
        };
    }

    public static function fromStravaType(string $stravaType): self
    {
        return match($stravaType) {
            'Run' => self::RUNNING,
            'Ride' => self::CYCLING,
            'Swim' => self::SWIMMING,
            'Walk' => self::WALKING,
            'Hike' => self::HIKING,
            'WeightTraining' => self::STRENGTH,
            'Yoga' => self::YOGA,
            'Workout' => self::WORKOUT,
            default => self::OTHER,
        };
    }
}
