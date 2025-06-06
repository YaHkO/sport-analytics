<?php

declare(strict_types=1);

namespace App\Sports\Application\UseCase\SyncActivities;

class SyncActivitiesResponse
{
    public function __construct(
        public readonly int $syncedCount,
        public readonly array $errors = []
    ) {}

    public function isSuccessful(): bool
    {
        return empty($this->errors);
    }

    public function hasPartialSuccess(): bool
    {
        return $this->syncedCount > 0 && !empty($this->errors);
    }
}
