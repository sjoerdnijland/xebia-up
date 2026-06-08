<?php
namespace App\Repository;
use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.position', 'ASC')
            ->getQuery()->getResult();
    }
}
