<?php

namespace App\Service;

use App\Entity\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DataImporter
{
    private $entityManager;
    private $apiUrl;
    private $defaultNationality;
    private $defaultResults;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $apiUrl,
        string $defaultNationality,
        int $defaultResults,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->apiUrl = $apiUrl;
        $this->defaultNationality = $defaultNationality;
        $this->defaultResults = $defaultResults;
        $this->passwordHasher = $passwordHasher;
    }

    public function importCustomers(string $nationality = null, int $results = null)
    {
        $nationality ??= $this->defaultNationality;
        $results ??= $this->defaultResults;

        $client = HttpClient::create();
        $response = $client->request('GET', sprintf("%s/?nat=%s&results=%d", $this->apiUrl, $nationality, $results));

        $data = $response->toArray();

        foreach ($data['results'] as $userData) {
            $customer = $this->entityManager->getRepository(Customers::class)->findOneBy(['email' => $userData['email']]);

            if (!$customer) {
                $customer = new Customers();
            }

            $customer->setUuid($userData['login']['uuid']);
            $customer->setTitle($userData['name']['title']);
            $customer->setFirstName($userData['name']['first']);
            $customer->setLastName($userData['name']['last']);
            $customer->setGender($userData['gender']);
            $customer->setEmail($userData['email']);
            $customer->setUsername($userData['login']['username']);
            $hashedPassword = $this->passwordHasher->hashPassword($customer, $userData['login']['password']);
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
        }

        $this->entityManager->flush();
    }
}