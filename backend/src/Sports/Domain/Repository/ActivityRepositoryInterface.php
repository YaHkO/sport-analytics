<?php

declare(strict_types=1);

namespace App\Sports\Domain\Repository;

use App\Sports\Domain\Entity\Activity;
use App\Sports\Domain\Entity\SportType;
use App\Shared\Domain\ValueObject\Id;

interface ActivityRepositoryInterface
{
    public function save(Activity $activity): void;

    public function findById(Id $id): ?Activity;

    public function findByExternalId(string $externalId): ?Activity;

    public function findAll(int $offset = 0, int $limit = 20): array;

    public function findBySportType(SportType $sportType, int $offset = 0, int $limit = 20): array;

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array;

    public function findByFilters(array $filters, int $offset = 0, int $limit = 20): array;

    public function countAll(): int;

    public function countBySportType(SportType $sportType): int;

    public function delete(Activity $activity): void;
}
