<?php

namespace App\Service;

use App\Entity\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DataImporter
{
    private $entityManager;
    private $apiUrl;
    private $defaultNationality;
    private $defaultResults;
    private $passwordHasher;
    private $logger;

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
    }

    public function importCustomers(string $nationality = null, int $results = null): string
    {
        $nationality ??= $this->defaultNationality;
        $results ??= $this->defaultResults;

        $client = HttpClient::create();
        $response = $client->request('GET', sprintf("%s/?nat=%s&results=%d", $this->apiUrl, $nationality, $results));

        $data = $response->toArray();
        $importedCount = 0;

        $batchSize = 20; // Define batch size for flushing
        $i = 0;

        foreach ($data['results'] as $userData) {
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
    }
}