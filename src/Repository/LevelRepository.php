<?php
namespace App\Repository;
use App\Entity\Level;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Level::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.depth', 'ASC')
            ->getQuery()->getResult();
    }
}
