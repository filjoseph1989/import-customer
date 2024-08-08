<?php

namespace App\Tests\Service;

use App\Entity\Customers;
use App\Service\DataImporter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DataImporterTest extends TestCase
{
    private $entityManager;
    private $passwordHasher;
    private $logger;
    private $lockFactory;
    private $httpClient;
    private $dataImporter;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $store = new FlockStore('/tmp');
        $this->lockFactory = new LockFactory($store);

        $mockResponse = new MockResponse(json_encode([
            'results' => $this->getResults()
        ]));

        $this->httpClient = new MockHttpClient($mockResponse);

        $this->dataImporter = $this->createDataImporter();
    }

    private function createDataImporter(): DataImporter
    {
        // Set environment variables directly in the test
        $apiUrl = getenv('API_URL') ?: 'http://example.com/api';
        $defaultNationality = getenv('DEFAULT_NATIONALITY') ?: 'AU';
        $defaultResults = (int) getenv('DEFAULT_RESULTS') ?: 1;

        return new DataImporter(
            $this->entityManager,
            $apiUrl,
            $defaultNationality,
            $defaultResults,
            $this->passwordHasher,
            $this->logger,
            $this->httpClient
        );
    }

    private function getResults(): array
    {
        return [
            [
                "gender" => "female",
                "name" => [
                    "title" => "Miss",
                    "first" => "Amy",
                    "last" => "Fortin"
                ],
                "location" => [
                    "street" => [
                        "number" => 3772,
                        "name" => "Arctic Way"
                    ],
                    "city" => "Shelbourne",
                    "state" => "Prince Edward Island",
                    "country" => "Australia",
                    "postcode" => "B4G 7F4",
                    "coordinates" => [
                        "latitude" => "-64.4509",
                        "longitude" => "28.6265"
                    ],
                    "timezone" => [
                        "offset" => "+3:00",
                        "description" => "Baghdad, Riyadh, Moscow, St. Petersburg"
                    ]
                ],
                "email" => "amy.fortin@example.com",
                "login" => [
                    "uuid" => "140862bb-eba5-4a51-a87a-a9bd9bb4a765",
                    "username" => "purpleostrich632",
                    "password" => "iverson3",
                    "salt" => "RMvNuejH",
                    "md5" => "c3309d1538f169b85604f905742e3458",
                    "sha1" => "a78b15144a2a07abfad3a08aa58d6bd04b54980c",
                    "sha256" => "819b5cade8febac7dd143140554c3d7c77a4d2af802a70d82b311d102b31e3a3"
                ],
                "dob" => [
                    "date" => "1985-08-08T00:09:08.148Z",
                    "age" => 38
                ],
                "registered" => [
                    "date" => "2019-01-18T08:51:46.332Z",
                    "age" => 5
                ],
                "phone" => "S72 Q43-3165",
                "cell" => "E18 B11-3216",
                "id" => [
                    "name" => "SIN",
                    "value" => "926242322"
                ],
                "picture" => [
                    "large" => "https://example.com/api/portraits/women/59.jpg",
                    "medium" => "https://example.com/api/portraits/med/women/59.jpg",
                    "thumbnail" => "https://example.com/api/portraits/thumb/women/59.jpg"
                ],
                "nat" => "AU"
            ]
        ];
    }

    public function testImportCustomersSuccessfully()
    {
        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($customer) {
                $this->assertInstanceOf(Customers::class, $customer);
                $this->assertEquals('amy.fortin@example.com', $customer->getEmail());
            });

        $this->entityManager->expects($this->atLeastOnce())->method('flush');

        $result = $this->dataImporter->importCustomers();

        $this->assertEquals('Successfully imported 1 customers.', $result);
    }

    public function testImportCustomersNoResults()
    {
        $mockResponse = new MockResponse(json_encode([
            'results' => []
        ]));

        $this->httpClient = new MockHttpClient($mockResponse);

        $this->dataImporter = $this->createDataImporter();

        $this->entityManager->expects($this->never())->method('persist');

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->dataImporter->importCustomers();

        $this->assertEquals('Successfully imported 0 customers.', $result);
    }

    public function testImportCustomersInvalidData()
    {
        $mockResponse = new MockResponse(json_encode([
            'results' => [
                [ "email" => "invalid-email" ]
            ]
        ]));

        $this->httpClient = new MockHttpClient($mockResponse);

        $this->dataImporter = $this->createDataImporter();

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->dataImporter->importCustomers();

        $this->assertEquals('Successfully imported 0 customers.', $result);
    }

    public function testImportCustomersApiFailure()
    {
        $mockResponse = new MockResponse('', ['http_code' => 500]);

        $this->httpClient = new MockHttpClient($mockResponse);

        $this->dataImporter = $this->createDataImporter();

        $this->entityManager->expects($this->never())->method('persist');

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->dataImporter->importCustomers();

        $this->assertEquals('Failed to fetch data from API.', $result);
    }

    public function testImportCustomersLockFailure()
    {
        // $lock = $this->createMock(\Symfony\Component\Lock\LockInterface::class);
        $lock = $this->createMock(\Symfony\Component\Lock\SharedLockInterface::class);
        $lock->expects($this->once())->method('acquire')->willReturn(false);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects($this->once())->method('createLock')->willReturn($lock);

        $this->dataImporter->setLockFactory($lockFactory);

        $result = $this->dataImporter->importCustomers();

        $this->assertEquals('Another import process is already running.', $result);
    }

    public function testImportCustomersDataFetchFailure()
    {
        $mockResponse = new MockResponse('', ['http_code' => 500]);

        $this->httpClient = new MockHttpClient($mockResponse);

        $this->dataImporter = $this->createDataImporter();

        $this->entityManager->expects($this->never())->method('persist');

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->dataImporter->importCustomers();

        $this->assertEquals('Failed to fetch data from API.', $result);
    }
}