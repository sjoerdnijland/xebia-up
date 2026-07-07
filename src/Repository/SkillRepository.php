<?php
namespace App\Repository;

use App\Entity\Category;
use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    /** @return Skill[] */
    public function findByCategoryOrdered(Category $category): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('s.position', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return Skill[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.ringSlug', 'ASC')
            ->addOrderBy('s.domainSlug', 'ASC')
            ->addOrderBy('s.position', 'ASC')
            ->getQuery()->getResult();
    }
}
