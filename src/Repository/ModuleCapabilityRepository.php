<?php

namespace App\Repository;

use App\Entity\ModuleCapability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ModuleCapabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleCapability::class);
    }

    /**
     * Return capability key for a given module + category, or null if none set.
     */
    public function findKey(string $moduleSlug, string $categorySlug): ?string
    {
        $row = $this->createQueryBuilder('mc')
            ->select('mc.capabilityKey')
            ->join('mc.module', 'm')
            ->join('mc.category', 'c')
            ->where('m.slug = :ms')
            ->andWhere('c.slug = :cs')
            ->setParameter('ms', $moduleSlug)
            ->setParameter('cs', $categorySlug)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        return $row['capabilityKey'] ?? null;
    }

    /**
     * @return array<string,string> map of category_slug => capability_key for the given module.
     */
    public function findAllForModule(string $moduleSlug): array
    {
        $rows = $this->createQueryBuilder('mc')
            ->select('c.slug AS catSlug, mc.capabilityKey')
            ->join('mc.module', 'm')
            ->join('mc.category', 'c')
            ->where('m.slug = :ms')
            ->setParameter('ms', $moduleSlug)
            ->getQuery()->getResult();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['catSlug']] = $row['capabilityKey'];
        }
        return $out;
    }
}
