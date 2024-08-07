<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataFetcher
{
    private $apiUrl;
    private $client;
    private $logger;

    public function __construct(string $apiUrl, HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->apiUrl = $apiUrl;
        $this->client = $client;
        $this->logger = $logger;
    }

    public function fetchData(string $nationality, int $results, int $maxRetries = 3, int $retryDelay = 2, string $httpMethod = 'GET', array $additionalParams = []): ?array
    {
        $attempt = 0;
        $queryParams = array_merge(
            [
                'nat' => strtoupper($nationality),
                'results' => $results
            ],
            $additionalParams
        );

        $url = sprintf("%s/?%s", $this->apiUrl, http_build_query($queryParams));

        while ($attempt < $maxRetries) {
            try {
                $response = $this->client->request($httpMethod, $url);
                return $response->toArray();
            } catch (\Exception $e) {
                $this->logger->warning(sprintf('Failed to fetch data from API. Attempt %d of %d.', $attempt + 1, $maxRetries), [
                    'error' => $e->getMessage()
                ]);
                sleep($retryDelay);
                $attempt++;
            }
        }
        return null;
    }
}