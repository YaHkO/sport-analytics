<?php

declare(strict_types=1);

namespace App\Sports\Infrastructure\ExternalService;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class StravaApiService
{
    private string $clientId;
    private string $clientSecret;
    private string $accessToken;
    private string $refreshToken;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $clientId = '',
        string $clientSecret = '',
        string $accessToken = '',
        string $refreshToken = ''
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function fetchActivities(?int $limit = null): array
    {
        // Si pas de token configuré, retourner des données de test
        if (empty($this->accessToken)) {
            $this->logger->info('Pas de token Strava configuré, utilisation de données de test');
            return $this->getFakeActivitiesData($limit);
        }

        try {
            $allActivities = [];
            $page = 1;
            $perPage = 200;

            do {
                $response = $this->httpClient->request('GET', 'https://www.strava.com/api/v3/athlete/activities', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                    ],
                    'query' => [
                        'page' => $page,
                        'per_page' => $perPage,
                    ],
                ]);

                if ($response->getStatusCode() === 401) {
                    $this->refreshAccessToken();
                    return $this->fetchActivities($limit);
                }

                $activities = $response->toArray();
                $allActivities = array_merge($allActivities, $activities);

                $page++;
            } while (
                count($activities) === $perPage &&
                (!$limit || count($allActivities) < $limit)
            );

            if ($limit) {
                $allActivities = array_slice($allActivities, 0, $limit);
            }

            return $allActivities;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des activités Strava: ' . $e->getMessage());

            // En cas d'erreur, retourner des données de test
            return $this->getFakeActivitiesData($limit);
        }
    }

    private function refreshAccessToken(): void
    {
        try {
            $response = $this->httpClient->request('POST', 'https://www.strava.com/oauth/token', [
                'body' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
            $this->refreshToken = $data['refresh_token'];

            $this->logger->info('Token Strava rafraîchi avec succès');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du rafraîchissement du token: ' . $e->getMessage());
            throw $e;
        }
    }

    // Données de test pour le développement
    private function getFakeActivitiesData(?int $limit = null): array
    {
        $activities = [
            [
                'id' => 'fake_1_' . time(),
                'name' => 'Course matinale DDD',
                'sport_type' => 'Run',
                'start_date' => (new \DateTime('-2 days'))->format('Y-m-d\TH:i:s\Z'),
                'distance' => 5200,
                'moving_time' => 1800, // 30 min
                'elapsed_time' => 1900,
                'average_speed' => 2.89, // ~10.4 km/h
                'average_heartrate' => 150,
                'max_heartrate' => 175,
                'total_elevation_gain' => 50,
                'average_cadence' => 85.5,
                'kilojoules' => 300,
            ],
            [
                'id' => 'fake_2_' . time(),
                'name' => 'Sortie vélo DDD',
                'sport_type' => 'Ride',
                'start_date' => (new \DateTime('-1 day'))->format('Y-m-d\TH:i:s\Z'),
                'distance' => 28000,
                'moving_time' => 3600, // 1h
                'elapsed_time' => 3800,
                'average_speed' => 7.78, // ~28 km/h
                'max_speed' => 12.5, // ~45 km/h
                'average_heartrate' => 140,
                'max_heartrate' => 165,
                'average_watts' => 180,
                'total_elevation_gain' => 350,
                'average_cadence' => 90,
                'kilojoules' => 650,
            ],
            [
                'id' => 'fake_3_' . time(),
                'name' => 'Natation DDD',
                'sport_type' => 'Swim',
                'start_date' => (new \DateTime())->format('Y-m-d\TH:i:s\Z'),
                'distance' => 1500,
                'moving_time' => 2400, // 40 min
                'elapsed_time' => 2700,
                'average_speed' => 0.625, // ~2.25 km/h
            ],
        ];

        if ($limit) {
            $activities = array_slice($activities, 0, $limit);
        }

        return $activities;
    }
}
