<?php

namespace App\Tests\Controller;

use App\Entity\Customers;
use App\Repository\CustomerRepository;
use App\Service\DataImporter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllerTest extends WebTestCase
{
    private $client;
    private $customerRepository;
    private $dataImporter;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->dataImporter = $this->createMock(DataImporter::class);

        // Replace service with mock in the container
        static::getContainer()->set(CustomerRepository::class, $this->customerRepository);
        static::getContainer()->set(DataImporter::class, $this->dataImporter);
    }

    protected function restoreExceptionHandler(): void
    {
        while (true) {
            $previousHandler = set_exception_handler(static fn() => null);

            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->restoreExceptionHandler();
    }

    public function testIndexReturnsAllCustomers()
    {
        $customer = (new Customers())
            ->setId(1)
            ->setUuid('1234-5678-91011')
            ->setTitle('Mr')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@example.com')
            ->setUsername('johndoe')
            ->setPhone('123-456-7890')
            ->setCell('098-765-4321')
            ->setNat('US')
            ->setCountry('USA')
            ->setGender('male')
            ->setDob(new \DateTime('1980-01-01'))
            ->setRegisteredDate(new \DateTime('2020-01-01'))
            ->setPictureThumbnail('http://example.com/thumb.jpg');

        $this->customerRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$customer]);

        $this->client->request('GET', '/customer');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals('john.doe@example.com', $data[0]['email']);
    }

    public function testIndexReturnsNoCustomers()
    {
        $this->customerRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $this->client->request('GET', '/customer');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(0, $data);
    }

    public function testShowReturnsCustomer()
    {
        $customer = (new Customers())
            ->setId(1)
            ->setUuid('1234-5678-91011')
            ->setTitle('Mr')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@example.com')
            ->setUsername('johndoe')
            ->setPhone('123-456-7890')
            ->setCell('098-765-4321')
            ->setNat('US')
            ->setCountry('USA')
            ->setGender('male')
            ->setDob(new \DateTime('1980-01-01'))
            ->setRegisteredDate(new \DateTime('2020-01-01'))
            ->setPictureThumbnail('http://example.com/thumb.jpg');

        $this->customerRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($customer);

        $this->client->request('GET', '/customer/1');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('john.doe@example.com', $data['email']);
    }

    public function testShowReturnsNotFound()
    {
        $this->customerRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->client->request('GET', '/customer/1');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Customer not found', $data['message']);
    }

    public function testImportCustomersSuccessfully()
    {
        $this->dataImporter->expects($this->once())
            ->method('importCustomers')
            ->with('US', 10);

        $this->client->request('GET', '/import-customer/AU/10');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Customers imported successfully.', $response->getContent());
    }

    public function testImportCustomersInvalidParams()
    {
        $this->dataImporter->expects($this->never())
            ->method('importCustomers');

        $this->client->request('GET', '/import-customer/INVALID/INVALID');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }
}