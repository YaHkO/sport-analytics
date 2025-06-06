<?php

declare(strict_types=1);


// Solution : Injection de dépendances correcte dans le controller
// src/Controller/TestController.php

namespace App\Sports\UI\Web\Controller;

use App\Sports\Domain\Entity\Activity;
use App\Sports\Domain\Entity\SportType;
use App\Sports\Domain\Entity\DataSource;
use App\Sports\Domain\ValueObject\Distance;
use App\Sports\Domain\ValueObject\Duration;
use App\Sports\Domain\ValueObject\Speed;
use App\Sports\Domain\ValueObject\HeartRate;
use App\Sports\Domain\Repository\ActivityRepositoryInterface;
use App\Sports\Infrastructure\ExternalService\StravaApiService;
use App\Sports\Application\UseCase\SyncActivities\SyncActivitiesCommand;
use App\Sports\Application\UseCase\SyncActivities\SyncActivitiesHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test', name: 'test_')]
class TestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private ActivityRepositoryInterface $activityRepository,
        private StravaApiService            $stravaApiService,
        private LoggerInterface             $logger
    )
    {
    }

    #[Route('/value-objects', name: 'value_objects', methods: ['GET'])]
    public function testValueObjects(): JsonResponse
    {
        try {
            // Test des Value Objects
            $distance = Distance::fromKilometers(5.2);
            $movingTime = Duration::fromSeconds(1800); // 30 minutes
            $averageSpeed = Speed::fromMetersPerSecond(2.89); // ~10.4 km/h
            $heartRate = new HeartRate(150);

            $results = [
                'distance_tests' => [
                    'from_km' => $distance->toKilometers(),
                    'to_meters' => $distance->toMeters(),
                    'formatted' => (string)$distance,
                ],
                'duration_tests' => [
                    'seconds' => $movingTime->toSeconds(),
                    'hours' => $movingTime->toHours(),
                    'formatted' => $movingTime->toFormattedString(),
                ],
                'speed_tests' => [
                    'ms' => $averageSpeed->toMetersPerSecond(),
                    'kmh' => $averageSpeed->toKmPerHour(),
                ],
                'heartrate_tests' => [
                    'bpm' => $heartRate->toBpm(),
                    'formatted' => (string)$heartRate,
                ],
                'enum_tests' => [
                    'sport_type' => SportType::RUNNING->value,
                    'sport_icon' => SportType::RUNNING->getIcon(),
                    'is_endurance' => SportType::RUNNING->isEndurance(),
                    'data_source' => DataSource::STRAVA->getDisplayName(),
                ]
            ];

            return $this->json([
                'success' => true,
                'message' => 'Value Objects fonctionnent parfaitement !',
                'tests' => $results
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/create-activity', name: 'create_activity', methods: ['GET'])]
    public function testCreateActivity(): JsonResponse
    {
        try {
            // Créer une activité de test
            $activity = new Activity(
                'test_' . time(),
                'Course de test DDD',
                SportType::RUNNING->value,
                new \DateTime(),
                Distance::fromKilometers(5.2),
                Duration::fromSeconds(1800),
                Duration::fromSeconds(1900),
                DataSource::STRAVA->value
            );

            // Ajouter des métriques
            $activity->updateMetrics(
                Distance::fromMeters(50), // elevation
                Speed::fromMetersPerSecond(2.89), // average speed
                Speed::fromMetersPerSecond(4.17), // max speed
                new HeartRate(150), // avg HR
                new HeartRate(180), // max HR
                85.5, // cadence
                null, // watts
                300 // kilojoules
            );

            $activity->setDescription('Activité de test créée via le controller DDD');

            // Sauvegarder via le repository
            $this->activityRepository->save($activity);

            return $this->json([
                'success' => true,
                'message' => 'Activité créée et sauvegardée !',
                'activity' => [
                    'id' => $activity->getId()->value(),
                    'external_id' => $activity->getExternalId(),
                    'name' => $activity->getName(),
                    'sport_type' => $activity->getSportType()->value,
                    'sport_icon' => $activity->getSportType()->getIcon(),
                    'distance_km' => $activity->getDistanceInKm(),
                    'moving_time' => $activity->getMovingTimeFormatted(),
                    'average_speed_kmh' => $activity->calculateAverageSpeedKmh(),
                    'is_endurance' => $activity->isEnduranceActivity(),
                    'has_hr_data' => $activity->hasHeartRateData(),
                    'has_power_data' => $activity->hasPowerData(),
                    'start_date' => $activity->getStartDate()->format('Y-m-d H:i:s'),
                    'source' => $activity->getSource(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/list-activities', name: 'list_activities', methods: ['GET'])]
    public function testListActivities(): JsonResponse
    {
        try {
            // Utiliser le repository injecté
            $activities = $this->activityRepository->findAll(0, 10);

            $result = [];
            foreach ($activities as $activity) {
                $result[] = [
                    'id' => $activity->getId()->value(),
                    'name' => $activity->getName(),
                    'sport_type' => $activity->getSportType()->value,
                    'sport_icon' => $activity->getSportType()->getIcon(),
                    'distance_km' => round($activity->getDistanceInKm(), 2),
                    'moving_time' => $activity->getMovingTimeFormatted(),
                    'average_speed_kmh' => $activity->calculateAverageSpeedKmh() ? round($activity->calculateAverageSpeedKmh(), 1) : null,
                    'start_date' => $activity->getStartDate()->format('Y-m-d H:i:s'),
                    'source' => $activity->getSource(),
                ];
            }

            return $this->json([
                'success' => true,
                'message' => count($activities) . ' activités trouvées',
                'count' => count($activities),
                'activities' => $result
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/sync-test', name: 'sync_test', methods: ['GET'])]
    public function testSyncActivities(): JsonResponse
    {
        try {
            // Utiliser les services injectés directement
            $command = new SyncActivitiesCommand('strava', 3);
            $handler = new SyncActivitiesHandler(
                $this->activityRepository,
                $this->stravaApiService,
                $this->logger
            );

            $response = $handler->handle($command);

            return $this->json([
                'success' => $response->isSuccessful(),
                'synced_count' => $response->syncedCount,
                'errors' => $response->errors,
                'message' => 'Use Case SyncActivities testé avec succès !',
                'details' => [
                    'source' => $command->source,
                    'limit' => $command->limit,
                    'is_partial_success' => $response->hasPartialSuccess(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/test-repository', name: 'test_repository', methods: ['GET'])]
    public function testRepository(): JsonResponse
    {
        try {
            // Test des méthodes du repository
            $totalActivities = $this->activityRepository->countAll();
            $recentActivities = $this->activityRepository->findAll(0, 5);

            // Test par sport type
            $runningCount = $this->activityRepository->countBySportType(SportType::RUNNING);
            $cyclingCount = $this->activityRepository->countBySportType(SportType::CYCLING);

            // Test par date
            $lastWeek = new \DateTime('-1 week');
            $now = new \DateTime();
            $recentActivitiesCount = count($this->activityRepository->findByDateRange($lastWeek, $now));

            return $this->json([
                'success' => true,
                'message' => 'Repository testé avec succès !',
                'stats' => [
                    'total_activities' => $totalActivities,
                    'recent_activities_count' => count($recentActivities),
                    'running_activities' => $runningCount,
                    'cycling_activities' => $cyclingCount,
                    'last_week_activities' => $recentActivitiesCount,
                ],
                'recent_activities' => array_map(function ($activity) {
                    return [
                        'name' => $activity->getName(),
                        'sport' => $activity->getSportType()->value,
                        'distance_km' => round($activity->getDistanceInKm(), 1),
                        'date' => $activity->getStartDate()->format('Y-m-d'),
                    ];
                }, $recentActivities)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/debug', name: 'debug', methods: ['GET'])]
    public function debug(): JsonResponse
    {
        try {
            return $this->json([
                'success' => true,
                'message' => 'Le routing fonctionne !',
                'timestamp' => date('Y-m-d H:i:s'),
                'available_routes' => [
                    'GET /test/value-objects' => 'Test des Value Objects',
                    'GET /test/create-activity' => 'Créer une activité de test',
                    'GET /test/list-activities' => 'Lister les activités (via repository)',
                    'GET /test/sync-test' => 'Tester la synchronisation Strava',
                    'GET /test/test-repository' => 'Tester toutes les méthodes du repository',
                    'GET /test/debug' => 'Cette route (debug)',
                ],
                'services_injected' => [
                    'ActivityRepository' => get_class($this->activityRepository),
                    'StravaService' => get_class($this->stravaApiService),
                    'EntityManager' => get_class($this->entityManager),
                    'Logger' => get_class($this->logger),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
