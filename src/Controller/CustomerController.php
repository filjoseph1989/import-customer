<?php

namespace App\Controller;

use App\Repository\CustomerRepository;
use App\Service\DataImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CustomerController extends AbstractController
{
    private $customerRepository;
    private $dataImporter;

    public function __construct(CustomerRepository $customerRepository, DataImporter $dataImporter)
    {
        $this->customerRepository = $customerRepository;
        $this->dataImporter = $dataImporter;
    }

    #[Route('/customer', name: 'app_customer')]
    public function index(): JsonResponse
    {
        $customers = $this->customerRepository->findAll();
        $data = [];

        foreach ($customers as $customer) {
            $data[] = [
                'id' => $customer->getId(),
                'uuid' => $customer->getUuid(),
                'name' => $customer->getTitle() . ' ' . $customer->getFirstName() . ' ' . $customer->getLastName(),
                'email' => $customer->getEmail(),
                'username' => $customer->getUsername(),
                'phone' => $customer->getPhone(),
                'cell' => $customer->getCell(),
                'nat' => $customer->getNat(),
                'gender' => $customer->getGender(),
                'dob' => $customer->getDob(),
                'registered' => $customer->getRegisteredDate(),
                'picture' => $customer->getPictureThumbnail(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/customer/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(int $id)
    {
        $customer = $this->customerRepository->find($id);

        if (!$customer) {
            return new JsonResponse([
                'message' => 'Customer not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = [
            'id' => $customer->getId(),
            'uuid' => $customer->getUuid(),
            'name' => $customer->getTitle() . ' ' . $customer->getFirstName() . ' ' . $customer->getLastName(),
            'email' => $customer->getEmail(),
            'username' => $customer->getUsername(),
            'phone' => $customer->getPhone(),
            'cell' => $customer->getCell(),
            'nat' => $customer->getNat(),
            'gender' => $customer->getGender(),
            'dob' => $customer->getDob(),
            'registered' => $customer->getRegisteredDate(),
            'picture' => $customer->getPictureThumbnail(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/import-customer/{nationality}/{results}', name: 'app_customer_create', methods: ['GET'])]
    public function importCustomers(string $nationality = null, int $results = null): Response
    {
        $nationality = strtoupper($nationality);
        $results = (int) $results;

        $this->dataImporter->importCustomers($nationality, $results);

        return new Response('Customers imported successfully.');
    }
}
