<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Module;
use App\Entity\ModuleType;
use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    public function findByCategory(Category $category, ?Role $role = null, ?ModuleType $type = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.categories', 'cat')
            ->join('m.level', 'l')
            ->where('cat = :category')
            ->andWhere('m.isActive = true')
            ->setParameter('category', $category)
            ->orderBy('l.depth', 'ASC')
            ->addOrderBy('m.position', 'ASC');

        if ($role !== null) {
            $qb->join('m.roles', 'r')
               ->andWhere('r = :role')
               ->setParameter('role', $role);
        }

        if ($type !== null) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
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
            ->andWhere('m.isActive = true')
            ->setParameter('category', $category)
            ->groupBy('r.slug')
            ->getQuery()->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['slug']] = (int) $row['cnt'];
        }
        return $counts;
    }

    public function countByCategoryAndType(Category $category): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('t.slug, COUNT(m.id) as cnt')
            ->join('m.categories', 'cat')
            ->join('m.type', 't')
            ->where('cat = :category')
            ->andWhere('m.isActive = true')
            ->setParameter('category', $category)
            ->groupBy('t.slug')
            ->getQuery()->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['slug']] = (int) $row['cnt'];
        }
        return $counts;
    }
}
