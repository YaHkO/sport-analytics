<?php

declare(strict_types=1);

namespace App\Sports\UI\Web\Controller;

use App\Sports\Domain\Repository\ActivityRepositoryInterface;
use App\Sports\Domain\Entity\SportType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stats', name: 'api_stats_')]
class StatsController extends AbstractController
{
    public function __construct(
        private ActivityRepositoryInterface $activityRepository
    ) {}

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function overview(Request $request): JsonResponse
    {
        try {
            $period = $request->query->get('period', 'month');
            $sport = $request->query->get('sport');

            $dateRange = $this->getDateRangeFromPeriod($period);
            $activities = $this->activityRepository->findByDateRange(
                $dateRange['start'],
                $dateRange['end']
            );

            // Filtrer par sport si spécifié
            if ($sport) {
                $sportType = SportType::from($sport);
                $activities = array_filter(
                    $activities,
                    fn($activity) => $activity->getSportType() === $sportType
                );
            }

            $stats = $this->calculateOverviewStats($activities);
            $trends = $this->calculateTrends($activities, $period);
            $sportBreakdown = $this->calculateSportBreakdown($activities);

            return $this->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'sport_filter' => $sport,
                    'date_range' => [
                        'start' => $dateRange['start']->format('Y-m-d'),
                        'end' => $dateRange['end']->format('Y-m-d'),
                    ],
                    'overview' => $stats,
                    'trends' => $trends,
                    'sport_breakdown' => $sportBreakdown,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du calcul des statistiques',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/chart-data', name: 'chart_data', methods: ['GET'])]
    public function chartData(Request $request): JsonResponse
    {
        try {
            $period = $request->query->get('period', 'month');
            $sport = $request->query->get('sport');
            $metric = $request->query->get('metric', 'distance');

            $dateRange = $this->getDateRangeFromPeriod($period);
            $activities = $this->activityRepository->findByDateRange(
                $dateRange['start'],
                $dateRange['end']
            );

            // Filtrer par sport si spécifié
            if ($sport) {
                $sportType = SportType::from($sport);
                $activities = array_filter(
                    $activities,
                    fn($activity) => $activity->getSportType() === $sportType
                );
            }

            $chartData = $this->groupActivitiesForChart($activities, $period, $metric);

            return $this->json([
                'success' => true,
                'data' => $chartData,
                'metadata' => [
                    'period' => $period,
                    'sport_filter' => $sport,
                    'metric' => $metric,
                    'total_points' => count($chartData),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la génération des données graphiques',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function getDateRangeFromPeriod(string $period): array
    {
        $end = new \DateTime();

        $start = match($period) {
            'week' => (new \DateTime())->modify('-1 week'),
            'month' => (new \DateTime())->modify('-1 month'),
            '3months' => (new \DateTime())->modify('-3 months'),
            '6months' => (new \DateTime())->modify('-6 months'),
            'year' => (new \DateTime())->modify('-1 year'),
            default => (new \DateTime())->modify('-1 month'),
        };

        return ['start' => $start, 'end' => $end];
    }

    private function calculateOverviewStats(array $activities): array
    {
        $stats = [
            'total_activities' => count($activities),
            'total_distance_km' => 0,
            'total_time_hours' => 0,
            'total_elevation_m' => 0,
            'average_speed_kmh' => 0,
            'with_hr_data' => 0,
            'with_power_data' => 0,
        ];

        $totalSpeedSum = 0;
        $speedCount = 0;

        foreach ($activities as $activity) {
            $stats['total_distance_km'] += $activity->getDistanceInKm();
            $stats['total_time_hours'] += $activity->getMovingTime()->toHours();

            if ($activity->hasElevationData()) {
                $stats['total_elevation_m'] += $activity->getTotalElevationGain()->toMeters();
            }

            if ($activity->hasSpeedData()) {
                $totalSpeedSum += $activity->calculateAverageSpeedKmh();
                $speedCount++;
            }

            if ($activity->hasHeartRateData()) {
                $stats['with_hr_data']++;
            }

            if ($activity->hasPowerData()) {
                $stats['with_power_data']++;
            }
        }

        // Moyennes
        if ($speedCount > 0) {
            $stats['average_speed_kmh'] = $totalSpeedSum / $speedCount;
        }

        // Arrondir les valeurs
        $stats['total_distance_km'] = round($stats['total_distance_km'], 1);
        $stats['total_time_hours'] = round($stats['total_time_hours'], 1);
        $stats['total_elevation_m'] = round($stats['total_elevation_m'], 0);
        $stats['average_speed_kmh'] = round($stats['average_speed_kmh'], 1);

        return $stats;
    }

    private function calculateTrends(array $activities, string $period): array
    {
        // Grouper par période (semaine pour le calcul de tendance)
        $grouped = [];
        $format = 'Y-W'; // Format semaine

        foreach ($activities as $activity) {
            $week = $activity->getStartDate()->format($format);

            if (!isset($grouped[$week])) {
                $grouped[$week] = [
                    'distance' => 0,
                    'time' => 0,
                    'count' => 0,
                ];
            }

            $grouped[$week]['distance'] += $activity->getDistanceInKm();
            $grouped[$week]['time'] += $activity->getMovingTime()->toHours();
            $grouped[$week]['count']++;
        }

        // Calculer les tendances (dernière vs précédente)
        $weeks = array_keys($grouped);
        sort($weeks);

        if (count($weeks) < 2) {
            return ['distance' => 0, 'activities' => 0, 'time' => 0];
        }

        $current = end($grouped);
        $previous = prev($grouped);

        return [
            'distance' => $this->calculatePercentageChange($previous['distance'], $current['distance']),
            'activities' => $this->calculatePercentageChange($previous['count'], $current['count']),
            'time' => $this->calculatePercentageChange($previous['time'], $current['time']),
        ];
    }

    private function calculateSportBreakdown(array $activities): array
    {
        $breakdown = [];

        foreach ($activities as $activity) {
            $sport = $activity->getSportType()->value;

            if (!isset($breakdown[$sport])) {
                $breakdown[$sport] = [
                    'sport' => $sport,
                    'icon' => $activity->getSportType()->getIcon(),
                    'count' => 0,
                    'distance_km' => 0,
                    'time_hours' => 0,
                    'is_endurance' => $activity->getSportType()->isEndurance(),
                ];
            }

            $breakdown[$sport]['count']++;
            $breakdown[$sport]['distance_km'] += $activity->getDistanceInKm();
            $breakdown[$sport]['time_hours'] += $activity->getMovingTime()->toHours();
        }

        // Arrondir et trier par nombre d'activités
        foreach ($breakdown as &$data) {
            $data['distance_km'] = round($data['distance_km'], 1);
            $data['time_hours'] = round($data['time_hours'], 1);
        }

        uasort($breakdown, fn($a, $b) => $b['count'] <=> $a['count']);

        return array_values($breakdown);
    }

    private function groupActivitiesForChart(array $activities, string $period, string $metric): array
    {
        $format = match($period) {
            'week', 'month' => 'Y-m-d',
            '3months', '6months', 'year' => 'Y-m',
            default => 'Y-m-d',
        };

        $grouped = [];

        foreach ($activities as $activity) {
            $date = $activity->getStartDate()->format($format);

            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'distance' => 0,
                    'time' => 0,
                    'count' => 0,
                    'elevation' => 0,
                ];
            }

            $grouped[$date]['distance'] += $activity->getDistanceInKm();
            $grouped[$date]['time'] += $activity->getMovingTime()->toHours();
            $grouped[$date]['count']++;

            if ($activity->hasElevationData()) {
                $grouped[$date]['elevation'] += $activity->getTotalElevationGain()->toMeters();
            }
        }

        // Trier par date et arrondir
        ksort($grouped);

        foreach ($grouped as &$data) {
            $data['distance'] = round($data['distance'], 1);
            $data['time'] = round($data['time'], 1);
            $data['elevation'] = round($data['elevation'], 0);
        }

        return array_values($grouped);
    }

    private function calculatePercentageChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
