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

        // Check JSON structure
        $this->assertCount(1, $data);
        $this->assertEquals('john.doe@example.com', $data[0]['email']);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('uuid', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('username', $data[0]);
        $this->assertArrayHasKey('phone', $data[0]);
        $this->assertArrayHasKey('cell', $data[0]);
        $this->assertArrayHasKey('nat', $data[0]);
        $this->assertArrayHasKey('country', $data[0]);
        $this->assertArrayHasKey('gender', $data[0]);
        $this->assertArrayHasKey('dob', $data[0]);
        $this->assertArrayHasKey('registered', $data[0]);
        $this->assertArrayHasKey('picture', $data[0]);
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

        // Check JSON structure
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
            ->setNat('AU')
            ->setCountry('Australia')
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

        // Check JSON structure
        $this->assertEquals('john.doe@example.com', $data['email']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('uuid', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('phone', $data);
        $this->assertArrayHasKey('cell', $data);
        $this->assertArrayHasKey('nat', $data);
        $this->assertArrayHasKey('country', $data);
        $this->assertArrayHasKey('gender', $data);
        $this->assertArrayHasKey('dob', $data);
        $this->assertArrayHasKey('registered', $data);
        $this->assertArrayHasKey('picture', $data);
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

        // Check JSON structure
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