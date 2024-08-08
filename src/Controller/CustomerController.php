<?php

namespace App\Controller;

use App\Repository\CustomerRepository;
use App\Service\DataImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CustomerController extends AbstractController
{
    private $customerRepository;
    private $dataImporter;
    private $params;

    public function __construct(
        CustomerRepository $customerRepository,
        DataImporter $dataImporter,
        ParameterBagInterface $params
    ) {
        $this->customerRepository = $customerRepository;
        $this->dataImporter = $dataImporter;
        $this->params = $params;
    }

    #[Route('/customers', name: 'app_customer', methods: ['GET'])]
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
                'country' => $customer->getCountry(),
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
            'country' => $customer->getCountry(),
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
        $nationality = $nationality ? strtoupper($nationality) : $this->params->get('default_nationality');
        $results = $results ?: $this->params->get('default_results');

        $this->dataImporter->importCustomers($nationality, $results);

        return new Response('Customers imported successfully.');
    }
}
