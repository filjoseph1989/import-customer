<?php

namespace App\Repository;

use App\Entity\Customers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Customers>
 */
class CustomerRepository extends ServiceEntityRepository
{
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, Customers::class);
        $this->logger = $logger;
    }

    public function saveCustomers(array $customers, int $batchSize = 20): string
    {
        $i = 0;
        foreach ($customers as $customer) {
            try {
                $this->getEntityManager()->persist($customer);
                if (($i % $batchSize) === 0) {
                    $this->getEntityManager()->flush();
                    $this->getEntityManager()->clear();
                }
                $i++;
            } catch (\Exception $e) {
                $this->logger->error('Error importing customer', [
                    'email' => $customer->getEmail() ?? '',
                    'uuid' => $customer->getUuid() ?? '',
                    'name' => sprintf('%s %s', $customer->getFirstName() ?? '', $customer->getLastName() ?? ''),
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }
        }

        try {
            if (count($customers) > 0) {
                $this->getEntityManager()->flush();
                $this->getEntityManager()->clear();
                return sprintf('Successfully imported %d customers.', count($customers));
            } else {
                return sprintf('Successfully imported 0 customers.');
            }
        } catch (\Throwable $th) {
            $this->logger->error('Error during final flush', [
                'error' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString()
            ]);
            return sprintf('Error during final flush: %s', $th->getMessage());
        }
    }
}