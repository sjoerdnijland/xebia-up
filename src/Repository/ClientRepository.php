<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /** @return Client[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()->getResult();
    }

    public function findBySlug(string $slug): ?Client
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByName(string $name): ?Client
    {
        return $this->findOneBy(['name' => $name]);
    }
}
