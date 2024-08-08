<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataImporter
{
    private $entityManager;
    private $defaultNationality;
    private $defaultResults;
    private $logger;
    private $lockFactory;
    private $dataFetcher;
    private $dataValidator;
    private $customerProcessor;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $apiUrl,
        string $defaultNationality,
        int $defaultResults,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger,
        HttpClientInterface $client
    ) {
        $this->entityManager = $entityManager;
        $this->defaultNationality = $defaultNationality;
        $this->defaultResults = $defaultResults;
        $this->logger = $logger;
        $this->dataFetcher = new DataFetcher($apiUrl, $client, $logger);
        $this->dataValidator = new DataValidator();
        $this->customerProcessor = new CustomerProcessor($passwordHasher, $entityManager);

        // Initialize the lock factory with the FlockStore
        $store = new FlockStore('/tmp'); // Use a suitable store
        $this->lockFactory = new LockFactory($store);
    }

    public function importCustomers(string $nationality = null, int $results = null): string
    {
        if ($nationality == null || $results == null) {
            return sprintf('Successfully imported 0 customers.');
        }

        $lock = $this->lockFactory->createLock('import_customer_lock', 3600);

        if (! $lock->acquire()) {
            $this->logger->warning('Another import process is already running.');
            return sprintf('Another import process is already running.');
        }

        try {
            $nationality ??= $this->defaultNationality;
            $results ??= $this->defaultResults;

            $data = $this->dataFetcher->fetchData($nationality, $results);

            if (!$data) {
                $this->logger->error('Failed to fetch data from API.');
                return 'Failed to fetch data from API.';
            }

            $customers = [];

            foreach ($data['results'] as $userData) {
                if (!$this->dataValidator->validate($userData)) {
                    $this->logger->error('Invalid data for user', ['userData' => $userData]);
                    continue;
                }

                $customers[] = $this->customerProcessor->process($userData);
            }

            return $this->save($customers);
        } finally {
            $lock->release();
        }
    }

    private function save(array $customers, int $batchSize = 20)
    {
        $i = 0;
        foreach ($customers as $customer) {
            try {
                $this->entityManager->persist($customer);
                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
                $i++;
            } catch (\Exception $e) {
                $this->logger->error('Error importing customer', [
                    'email' => $customer['email'] ?? '',
                    'uuid' => $customer['login']['uuid'] ?? '',
                    'name' => sprintf('%s %s', $customer['name']['first'] ?? '', $customer['name']['last'] ?? ''),
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }
        }

        try {
            if (count($customers) > 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $this->logger->info(sprintf('Successfully imported %d customers.', count($customers)));
                return sprintf('Successfully imported %d customers.', count($customers));
            } else {
                $this->logger->info(sprintf('Successfully imported 0 customers.'));
                return sprintf('Successfully imported 0 customers.');
            }
        } catch (\Throwable $th) {
            $this->logger->error('Error during final flush', [
                'error' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString()
            ]);
            return sprintf('Error during final flush: %s', $th->getMessage());
        }
    }

    public function setLockFactory(LockFactory $lockFactory): void
    {
        $this->lockFactory = $lockFactory;
    }
}