<?php

declare(strict_types=1);

// src/Sports/UI/Web/Controller/ActivityController.php
namespace App\Sports\UI\Web\Controller;

use App\Sports\Domain\Repository\ActivityRepositoryInterface;
use App\Sports\Domain\Entity\SportType;
use App\Sports\Application\UseCase\SyncActivities\SyncActivitiesCommand;
use App\Sports\Application\UseCase\SyncActivities\SyncActivitiesHandler;
use App\Shared\Domain\ValueObject\Id;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activities', name: 'api_activities_')]
class ActivityController extends AbstractController
{
    public function __construct(
        private ActivityRepositoryInterface $activityRepository,
        private SyncActivitiesHandler $syncHandler
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = min(100, max(10, $request->query->getInt('limit', 20)));
            $sport = $request->query->get('sport');
            $startDate = $request->query->get('start_date');
            $endDate = $request->query->get('end_date');
            $source = $request->query->get('source');

            // Construction des filtres
            $filters = [];
            if ($sport) $filters['sport_type'] = $sport;
            if ($startDate) $filters['start_date'] = $startDate;
            if ($endDate) $filters['end_date'] = $endDate;
            if ($source) $filters['source'] = $source;

            $offset = ($page - 1) * $limit;

            // Récupération des données
            $activities = $this->activityRepository->findByFilters($filters, $offset, $limit);
            $total = $this->activityRepository->countAll();

            // Transformation en DTO
            $activitiesData = array_map([$this, 'activityToArray'], $activities);

            return $this->json([
                'success' => true,
                'data' => $activitiesData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit),
                    'has_next' => $page < ceil($total / $limit),
                    'has_previous' => $page > 1,
                ],
                'filters_applied' => $filters,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des activités',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        try {
            $activityId = Id::fromString($id);
            $activity = $this->activityRepository->findById($activityId);

            if (!$activity) {
                return $this->json([
                    'success' => false,
                    'error' => 'Activité non trouvée',
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => $this->activityToArray($activity, true), // Mode détaillé
            ]);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => 'ID invalide',
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération de l\'activité',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/sports', name: 'sports', methods: ['GET'])]
    public function availableSports(): JsonResponse
    {
        try {
            $sports = [];

            foreach (SportType::cases() as $sportType) {
                $count = $this->activityRepository->countBySportType($sportType);
                if ($count > 0) {
                    $sports[] = [
                        'value' => $sportType->value,
                        'label' => ucfirst($sportType->value),
                        'count' => $count,
                        'icon' => $sportType->getIcon(),
                        'is_endurance' => $sportType->isEndurance(),
                    ];
                }
            }

            // Trier par nombre d'activités (décroissant)
            usort($sports, fn($a, $b) => $b['count'] <=> $a['count']);

            return $this->json([
                'success' => true,
                'data' => $sports,
                'total_sports' => count($sports),
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des sports',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/sync', name: 'sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?: [];
            $source = $data['source'] ?? 'strava';
            $limit = isset($data['limit']) ? (int)$data['limit'] : null;

            $command = new SyncActivitiesCommand($source, $limit);
            $response = $this->syncHandler->handle($command);

            $statusCode = $response->isSuccessful() ? 200 : ($response->hasPartialSuccess() ? 206 : 500);

            return $this->json([
                'success' => $response->isSuccessful(),
                'synced_count' => $response->syncedCount,
                'errors' => $response->errors,
                'has_partial_success' => $response->hasPartialSuccess(),
                'message' => $this->getSyncMessage($response),
                'source' => $source,
            ], $statusCode);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la synchronisation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function activityToArray($activity, bool $detailed = false): array
    {
        $data = [
            'id' => $activity->getId()->value(),
            'external_id' => $activity->getExternalId(),
            'name' => $activity->getName(),
            'sport_type' => $activity->getSportType()->value,
            'sport_icon' => $activity->getSportType()->getIcon(),
            'start_date' => $activity->getStartDate()->format('Y-m-d H:i:s'),
            'distance_km' => round($activity->getDistanceInKm(), 2),
            'moving_time_formatted' => $activity->getMovingTimeFormatted(),
            'moving_time_seconds' => $activity->getMovingTime()->toSeconds(),
            'source' => $activity->getSource(),
            'is_endurance' => $activity->isEnduranceActivity(),
        ];

        // Métriques conditionnelles
        if ($activity->hasSpeedData()) {
            $data['average_speed_kmh'] = round($activity->calculateAverageSpeedKmh(), 1);
        }

        if ($activity->hasHeartRateData()) {
            $data['average_heartrate'] = $activity->getAverageHeartrate()->toBpm();
            $data['intensity_level'] = $activity->getIntensityLevel();
        }

        if ($activity->hasElevationData()) {
            $data['elevation_m'] = round($activity->getTotalElevationGain()->toMeters(), 0);
        }

        if ($activity->hasPowerData()) {
            $data['average_watts'] = $activity->getAverageWatts();
        }

        // Mode détaillé
        if ($detailed) {
            $data = array_merge($data, [
                'elapsed_time_seconds' => $activity->getElapsedTime()->toSeconds(),
                'elapsed_time_formatted' => $activity->getElapsedTime()->toFormattedString(),
                'description' => $activity->getDescription(),
                'created_at' => $activity->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $activity->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);

            // Métriques détaillées conditionnelles
            if ($activity->getMaxSpeed()) {
                $data['max_speed_kmh'] = round($activity->calculateMaxSpeedKmh(), 1);
            }

            if ($activity->getMaxHeartrate()) {
                $data['max_heartrate'] = $activity->getMaxHeartrate()->toBpm();
            }

            if ($activity->getAverageCadence()) {
                $data['average_cadence'] = $activity->getAverageCadence();
            }

            if ($activity->getKilojoules()) {
                $data['kilojoules'] = $activity->getKilojoules();
            }
        }

        return $data;
    }

    private function getSyncMessage($response): string
    {
        if ($response->isSuccessful()) {
            return "{$response->syncedCount} activités synchronisées avec succès";
        }

        if ($response->hasPartialSuccess()) {
            $errorCount = count($response->errors);
            return "{$response->syncedCount} activités synchronisées, {$errorCount} erreurs";
        }

        return "Échec de la synchronisation";
    }
}
