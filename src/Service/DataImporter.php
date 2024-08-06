<?php

namespace App\Service;

use App\Entity\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;

class DataImporter
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function importCustomers()
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://randomuser.me/api/?nat=AU&results=1');

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
            $customer->setPassword($userData['login']['password']);
            $customer->setMd5Password(md5($userData['login']['password']));
            $customer->setSalt($userData['login']['salt']);
            $customer->setSha1Password($userData['login']['sha1']);
            $customer->setSha256Password($userData['login']['sha256']);
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