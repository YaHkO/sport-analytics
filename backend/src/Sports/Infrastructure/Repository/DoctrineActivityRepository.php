<?php

declare(strict_types=1);

namespace App\Sports\Infrastructure\Repository;

use App\Sports\Domain\Entity\Activity;
use App\Sports\Domain\Entity\SportType;
use App\Sports\Domain\Repository\ActivityRepositoryInterface;
use App\Shared\Domain\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineActivityRepository extends ServiceEntityRepository implements ActivityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function save(Activity $activity): void
    {
        $this->getEntityManager()->persist($activity);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?Activity
    {
        return $this->find($id->value());
    }

    public function findByExternalId(string $externalId): ?Activity
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    public function findAll(int $offset = 0, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.startDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBySportType(SportType $sportType, int $offset = 0, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.sportType = :sportType')
            ->setParameter('sportType', $sportType->value)
            ->orderBy('a.startDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.startDate >= :startDate')
            ->andWhere('a.startDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters, int $offset = 0, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('a');

        $this->applyFilters($qb, $filters);

        return $qb
            ->orderBy('a.startDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countBySportType(SportType $sportType): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.sportType = :sportType')
            ->setParameter('sportType', $sportType->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function delete(Activity $activity): void
    {
        $this->getEntityManager()->remove($activity);
        $this->getEntityManager()->flush();
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['sport_type'])) {
            $qb->andWhere('a.sportType = :sportType')
                ->setParameter('sportType', $filters['sport_type']);
        }

        if (isset($filters['start_date'])) {
            $qb->andWhere('a.startDate >= :startDate')
                ->setParameter('startDate', new \DateTime($filters['start_date']));
        }

        if (isset($filters['end_date'])) {
            $qb->andWhere('a.startDate <= :endDate')
                ->setParameter('endDate', new \DateTime($filters['end_date']));
        }

        if (isset($filters['source'])) {
            $qb->andWhere('a.source = :source')
                ->setParameter('source', $filters['source']);
        }
    }
}
