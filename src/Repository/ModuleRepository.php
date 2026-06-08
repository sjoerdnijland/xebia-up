<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Module;
use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    public function findByCategory(Category $category, ?Role $role = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.categories', 'cat')
            ->join('m.level', 'l')
            ->where('cat = :category')
            ->setParameter('category', $category)
            ->orderBy('l.depth', 'ASC')
            ->addOrderBy('m.position', 'ASC');

        if ($role !== null) {
            $qb->join('m.roles', 'r')
               ->andWhere('r = :role')
               ->setParameter('role', $role);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByCategoryAndRole(Category $category): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('r.slug, COUNT(m.id) as cnt')
            ->join('m.categories', 'cat')
            ->join('m.roles', 'r')
            ->where('cat = :category')
            ->setParameter('category', $category)
            ->groupBy('r.slug')
            ->getQuery()->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['slug']] = (int) $row['cnt'];
        }
        return $counts;
    }
}
