<?php

namespace App\Service;

use App\Entity\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerProcessor
{
    private $passwordHasher;
    private $entityManager;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    public function process(array $userData): Customers
    {
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

        return $customer;
    }
}