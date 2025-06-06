<?php

declare(strict_types=1);

namespace App\Sports\Application\UseCase\SyncActivities;

use App\Shared\Application\Command\CommandInterface;

class SyncActivitiesCommand implements CommandInterface
{
    public function __construct(
        public readonly string $source = 'strava',
        public readonly ?int $limit = null,
        public readonly ?\DateTimeInterface $startDate = null
    ) {}
}
