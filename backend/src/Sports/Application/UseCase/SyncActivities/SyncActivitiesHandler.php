<?php

declare(strict_types=1);

namespace App\Sports\Application\UseCase\SyncActivities;

use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Command\CommandInterface; // Importer l'interface de base
use App\Sports\Domain\Repository\ActivityRepositoryInterface;
use App\Sports\Infrastructure\ExternalService\StravaApiService;
use App\Sports\Domain\Entity\Activity;
use App\Sports\Domain\Entity\SportType;
use App\Sports\Domain\Entity\DataSource;
use App\Sports\Domain\ValueObject\Distance;
use App\Sports\Domain\ValueObject\Duration;
use App\Sports\Domain\ValueObject\Speed;
use App\Sports\Domain\ValueObject\HeartRate;
use Psr\Log\LoggerInterface;

class SyncActivitiesHandler implements CommandHandlerInterface
{
    public function __construct(
        private ActivityRepositoryInterface $activityRepository,
        private StravaApiService $stravaApiService,
        private LoggerInterface $logger
    ) {}

    // Signature compatible avec l'interface
    public function handle(CommandInterface $command): mixed
    {
        // Vérification de type au runtime
        if (!$command instanceof SyncActivitiesCommand) {
            throw new \InvalidArgumentException('Expected SyncActivitiesCommand, got ' . get_class($command));
        }

        return $this->handleSyncActivities($command);
    }

    // Méthode privée avec le type spécifique
    private function handleSyncActivities(SyncActivitiesCommand $command): SyncActivitiesResponse
    {
        $syncedCount = 0;
        $errors = [];

        try {
            $activities = $this->fetchActivitiesFromSource($command);

            foreach ($activities as $activityData) {
                try {
                    if ($this->processActivity($activityData, $command->source)) {
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Erreur pour l'activité {$activityData['id']}: " . $e->getMessage();
                    $this->logger->error('Erreur sync activité', [
                        'activity_id' => $activityData['id'],
                        'error' => $e->getMessage()
                    ]);
                }

                if ($command->limit && $syncedCount >= $command->limit) {
                    break;
                }
            }

        } catch (\Exception $e) {
            $errors[] = "Erreur générale de synchronisation: " . $e->getMessage();
            $this->logger->error('Erreur sync générale', ['error' => $e->getMessage()]);
        }

        return new SyncActivitiesResponse($syncedCount, $errors);
    }

    private function fetchActivitiesFromSource(SyncActivitiesCommand $command): array
    {
        return match($command->source) {
            'strava' => $this->stravaApiService->fetchActivities($command->limit),
            default => throw new \InvalidArgumentException("Source '{$command->source}' non supportée")
        };
    }

    private function processActivity(array $activityData, string $source): bool
    {
        // Vérifier si l'activité existe déjà
        $existingActivity = $this->activityRepository->findByExternalId((string)$activityData['id']);
        if ($existingActivity) {
            return false;
        }

        // Créer l'activité
        $activity = new Activity(
            (string)$activityData['id'],
            $activityData['name'],
            SportType::fromStravaType($activityData['sport_type'])->value,
            new \DateTime($activityData['start_date']),
            Distance::fromMeters($activityData['distance']),
            Duration::fromSeconds($activityData['moving_time']),
            Duration::fromSeconds($activityData['elapsed_time']),
            $source
        );

        // Ajouter les métriques optionnelles
        $activity->updateMetrics(
            isset($activityData['total_elevation_gain']) ? Distance::fromMeters($activityData['total_elevation_gain']) : null,
            isset($activityData['average_speed']) ? Speed::fromMetersPerSecond($activityData['average_speed']) : null,
            isset($activityData['max_speed']) ? Speed::fromMetersPerSecond($activityData['max_speed']) : null,
            isset($activityData['average_heartrate']) ? new HeartRate($activityData['average_heartrate']) : null,
            isset($activityData['max_heartrate']) ? new HeartRate($activityData['max_heartrate']) : null,
            $activityData['average_cadence'] ?? null,
            $activityData['average_watts'] ?? null,
            $activityData['kilojoules'] ?? null
        );

        if (isset($activityData['description'])) {
            $activity->setDescription($activityData['description']);
        }

        $this->activityRepository->save($activity);
        return true;
    }
}
