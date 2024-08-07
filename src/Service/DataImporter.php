<?php

namespace App\Service;

use App\Entity\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DataImporter
{
    private $entityManager;
    private $apiUrl;
    private $defaultNationality;
    private $defaultResults;
    private $passwordHasher;
    private $logger;
    private $lockFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $apiUrl,
        string $defaultNationality,
        int $defaultResults,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->apiUrl = $apiUrl;
        $this->defaultNationality = $defaultNationality;
        $this->defaultResults = $defaultResults;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;

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

            $client = HttpClient::create();
            $data = $this->fetchDataWithRetry($client, $nationality, $results);

            $importedCount = 0;

            $batchSize = 20; // Define batch size for flushing
            $i = 0;

            foreach ($data['results'] as $userData) {
                // Validate required fields
                if (!$this->validateUserData($userData)) {
                    $this->logger->error('Invalid data for user', [
                        'userData' => $userData
                    ]);
                    continue;
                }

                try {
                    $customer = $this->entityManager->getRepository(Customers::class)->findOneBy(['email' => $userData['email']]);

                    if (!$customer) {
                        $customer = new Customers();
                    }

                    $hashedPassword = $this->passwordHasher->hashPassword($customer, $userData['login']['password']);

                    $customer->setUuid($userData['login']['uuid']);
                    $customer->setTitle($userData['name']['title']);
                    $customer->setFirstName($userData['name']['first']);
                    $customer->setLastName($userData['name']['last']);
                    $customer->setGender($userData['gender']);
                    $customer->setEmail($userData['email']);
                    $customer->setUsername($userData['login']['username']);
                    $customer->setPassword($hashedPassword);
                    $customer->setDob(new \DateTime($userData['dob']['date']));
                    $customer->setRegisteredDate(new \DateTime($userData['registered']['date']));
                    $customer->setPhone($userData['phone']);
                    $customer->setCell($userData['cell']);
                    $customer->setNat($userData['nat']);
                    $customer->setPictureLarge($userData['picture']['large']);
                    $customer->setPictureMedium($userData['picture']['medium']);
                    $customer->setPictureThumbnail($userData['picture']['thumbnail']);

                    $this->entityManager->persist($customer);
                    $importedCount++;

                    if (($i % $batchSize) === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear(); // Detaches all objects from Doctrine to avoid memory issues
                    }
                    $i++;
                } catch (\Exception $e) {
                    $this->logger->error('Error importing customer', [
                        'email' => $userData['email'],
                        'uuid' => $userData['login']['uuid'],
                        'name' => sprintf('%s %s', $userData['name']['first'], $userData['name']['last']),
                        'error' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString()
                    ]);
                }
            }

            try {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $this->logger->info(sprintf('Successfully imported %d customers.', $importedCount));
                return sprintf('Successfully imported %d customers.', $importedCount);
            } catch (\Throwable $th) {
                $this->logger->error('Error during final flush', [
                    'error' => $th->getMessage(),
                    'stack_trace' => $th->getTraceAsString()
                ]);
                return sprintf('Error during final flush: %s', $th->getMessage());
            }
        } finally {
            $lock->release();
        }
    }

    private function validateUserData(array $userData): bool
    {
        // Basic validation
        if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (empty($userData['login']['uuid']) || empty($userData['name']['first']) || empty($userData['name']['last']) || empty($userData['dob']['date']) || empty($userData['registered']['date'])) {
            return false;
        }

        // Additional validations
        try {
            new \DateTime($userData['dob']['date']);
            new \DateTime($userData['registered']['date']);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function fetchDataWithRetry($client, $nationality, $results, $maxRetries = 3, $retryDelay = 2): ?array
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = $client->request('GET', sprintf("%s/?nat=%s&results=%d", $this->apiUrl, strtoupper($nationality), $results));
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