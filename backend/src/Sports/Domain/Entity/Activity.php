<?php

declare(strict_types=1);


// src/Sports/Domain/Entity/Activity.php - Version clean sans suffixes
namespace App\Sports\Domain\Entity;

use App\Sports\Domain\ValueObject\Distance;
use App\Sports\Domain\ValueObject\Duration;
use App\Sports\Domain\ValueObject\HeartRate;
use App\Sports\Domain\ValueObject\Speed;
use App\Shared\Domain\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'activities')]
class Activity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'external_id', type: 'string', length: 255, unique: true)]
    private string $externalId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50, name: 'sport_type')]
    private string $sportType;

    #[ORM\Column(type: 'datetime', name: 'start_date')]
    private \DateTimeInterface $startDate;

    // Value Objects obligatoires (toujours présents)
    #[ORM\Embedded(class: Distance::class, columnPrefix: 'distance_')]
    private Distance $distance;

    #[ORM\Embedded(class: Duration::class, columnPrefix: 'moving_time_')]
    private Duration $movingTime;

    #[ORM\Embedded(class: Duration::class, columnPrefix: 'elapsed_time_')]
    private Duration $elapsedTime;

    // Métriques optionnelles stockées comme primitives (noms simples)
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $elevation = null;

    #[ORM\Column(type: 'float', nullable: true, name: 'average_speed')]
    private ?float $averageSpeed = null;

    #[ORM\Column(type: 'float', nullable: true, name: 'max_speed')]
    private ?float $maxSpeed = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'average_heartrate')]
    private ?int $averageHeartrate = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'max_heartrate')]
    private ?int $maxHeartrate = null;

    #[ORM\Column(type: 'float', nullable: true, name: 'average_cadence')]
    private ?float $averageCadence = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'average_watts')]
    private ?int $averageWatts = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $kilojoules = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $source;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    private \DateTimeInterface $updatedAt;

    public function __construct(
        string             $externalId,
        string             $name,
        string             $sportType,
        \DateTimeInterface $startDate,
        Distance           $distance,
        Duration           $movingTime,
        Duration           $elapsedTime,
        string             $source
    )
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->externalId = $externalId;
        $this->name = $name;
        $this->sportType = $sportType;
        $this->startDate = $startDate;
        $this->distance = $distance;
        $this->movingTime = $movingTime;
        $this->elapsedTime = $elapsedTime;
        $this->source = $source;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Méthodes pour mettre à jour les métriques optionnelles
    public function updateMetrics(
        ?Distance  $totalElevationGain = null,
        ?Speed     $averageSpeed = null,
        ?Speed     $maxSpeed = null,
        ?HeartRate $averageHeartrate = null,
        ?HeartRate $maxHeartrate = null,
        ?float     $averageCadence = null,
        ?int       $averageWatts = null,
        ?int       $kilojoules = null
    ): void
    {
        // Stocker les valeurs primitives (les Value Objects documentent les unités)
        $this->elevation = $totalElevationGain?->toMeters();
        $this->averageSpeed = $averageSpeed?->toMetersPerSecond();
        $this->maxSpeed = $maxSpeed?->toMetersPerSecond();
        $this->averageHeartrate = $averageHeartrate?->toBpm();
        $this->maxHeartrate = $maxHeartrate?->toBpm();
        $this->averageCadence = $averageCadence;
        $this->averageWatts = $averageWatts;
        $this->kilojoules = $kilojoules;
        $this->updatedAt = new \DateTime();
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new \DateTime();
    }

    // Getters qui retournent les Value Objects (conversion à la volée)
    public function getId(): Id
    {
        return Id::fromString($this->id);
    }

    public function getSportType(): SportType
    {
        return SportType::from($this->sportType);
    }

    public function getDistance(): Distance
    {
        return $this->distance;
    }

    public function getMovingTime(): Duration
    {
        return $this->movingTime;
    }

    public function getElapsedTime(): Duration
    {
        return $this->elapsedTime;
    }

    public function getTotalElevationGain(): ?Distance
    {
        return $this->elevation ? Distance::fromMeters($this->elevation) : null;
    }

    public function getAverageSpeed(): ?Speed
    {
        return $this->averageSpeed ? Speed::fromMetersPerSecond($this->averageSpeed) : null;
    }

    public function getMaxSpeed(): ?Speed
    {
        return $this->maxSpeed ? Speed::fromMetersPerSecond($this->maxSpeed) : null;
    }

    public function getAverageHeartrate(): ?HeartRate
    {
        return $this->averageHeartrate ? new HeartRate($this->averageHeartrate) : null;
    }

    public function getMaxHeartrate(): ?HeartRate
    {
        return $this->maxHeartrate ? new HeartRate($this->maxHeartrate) : null;
    }

    // Méthodes business déléguées aux Value Objects
    public function getDistanceInKm(): float
    {
        return $this->distance->toKilometers();
    }

    public function getMovingTimeFormatted(): string
    {
        return $this->movingTime->toFormattedString();
    }

    public function calculateAverageSpeedKmh(): ?float
    {
        return $this->getAverageSpeed()?->toKmPerHour();
    }

    public function calculateMaxSpeedKmh(): ?float
    {
        return $this->getMaxSpeed()?->toKmPerHour();
    }

    public function isEnduranceActivity(): bool
    {
        return $this->getSportType()->isEndurance();
    }

    public function hasHeartRateData(): bool
    {
        return $this->averageHeartrate !== null;
    }

    public function hasPowerData(): bool
    {
        return $this->averageWatts !== null;
    }

    public function hasElevationData(): bool
    {
        return $this->elevation !== null;
    }

    public function hasSpeedData(): bool
    {
        return $this->averageSpeed !== null;
    }

    // Getters simples pour les données primitives
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function getAverageCadence(): ?float
    {
        return $this->averageCadence;
    }

    public function getAverageWatts(): ?int
    {
        return $this->averageWatts;
    }

    public function getKilojoules(): ?int
    {
        return $this->kilojoules;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    // Méthodes utilitaires pour l'affichage
    public function getFormattedElevation(): string
    {
        return $this->elevation ? number_format($this->elevation, 0) . ' m' : 'N/A';
    }

    public function getFormattedAverageSpeed(): string
    {
        return $this->averageSpeed ? number_format($this->averageSpeed * 3.6, 1) . ' km/h' : 'N/A';
    }

    public function getIntensityLevel(): string
    {
        if (!$this->averageHeartrate) {
            return 'Unknown';
        }

        return match (true) {
            $this->averageHeartrate < 120 => 'Easy',
            $this->averageHeartrate < 140 => 'Moderate',
            $this->averageHeartrate < 160 => 'Hard',
            default => 'Very Hard',
        };
    }
}
