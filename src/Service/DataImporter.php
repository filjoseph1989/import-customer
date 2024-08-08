<?php

namespace App\Service;

use App\Repository\CustomerRepository;
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
    private $customerRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $apiUrl,
        string $defaultNationality,
        int $defaultResults,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger,
        HttpClientInterface $client,
        CustomerRepository $customerRepository
    ) {
        $this->entityManager = $entityManager;
        $this->defaultNationality = $defaultNationality;
        $this->defaultResults = $defaultResults;
        $this->logger = $logger;
        $this->dataFetcher = new DataFetcher($apiUrl, $client, $logger);
        $this->dataValidator = new DataValidator();
        $this->customerProcessor = new CustomerProcessor($passwordHasher, $entityManager);
        $this->customerRepository = $customerRepository;

        // Initialize the lock factory with the FlockStore
        $store = new FlockStore('/tmp'); // Use a suitable store
        $this->lockFactory = new LockFactory($store);
    }

    public function importCustomers(string $nationality = null, int $results = null): string
    {
        $lock = $this->lockFactory->createLock('import_customer_lock', 3600);

        if (! $lock->acquire()) {
            $this->logger->warning('Another import process is already running.');
            return sprintf('Another import process is already running.');
        }

        try {
            $nationality ??= $this->defaultNationality;
            $results ??= $this->defaultResults;

            if ($nationality == null || $results == null) {
                return sprintf('Successfully imported 0 customers.');
            }

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

            if (count($customers) == 0) {
                return sprintf('Successfully imported 0 customers.');
            }

            $result = $this->customerRepository->saveCustomers($customers);

            return $result;
        } finally {
            $lock->release();
        }
    }

    public function setLockFactory(LockFactory $lockFactory): void
    {
        $this->lockFactory = $lockFactory;
    }
}