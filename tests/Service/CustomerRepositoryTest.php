<?php

namespace App\Tests\Repository;

use App\Entity\Customers;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomerRepositoryTest extends TestCase
{
    private $entityManager;
    private $customerRepository;
    private $logger;
    private $classMetadata;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->classMetadata->name = Customers::class;

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Customers::class)
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(Customers::class)
            ->willReturn($this->classMetadata);

        $this->customerRepository = new CustomerRepository($registry, $this->logger);
    }

    public function testSaveCustomers()
    {
        $customer1 = new Customers();
        $customer2 = new Customers();

        $customers = [$customer1, $customer2];

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr(
                $this->equalTo($customer1),
                $this->equalTo($customer2)
            )
            );

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $result = $this->customerRepository->saveCustomers($customers);

        $this->assertEquals('Successfully imported 2 customers.', $result);
    }
}
