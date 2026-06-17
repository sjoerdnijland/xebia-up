<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Journey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JourneyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journey::class);
    }

    /** @return Journey[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.client', 'c')->addSelect('c')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('j.position', 'ASC')
            ->addOrderBy('j.id', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return Journey[] */
    public function findByClient(Client $client): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.client = :client')->setParameter('client', $client)
            ->orderBy('j.position', 'ASC')
            ->addOrderBy('j.id', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * Journeys whose moduleSlugs JSON contains the given slug. Portable across
     * SQLite/MySQL by doing a coarse LIKE filter then exact-checking in PHP.
     *
     * @return Journey[]
     */
    public function findContainingSlug(string $slug): array
    {
        if ($slug === '') {
            return [];
        }
        $candidates = $this->createQueryBuilder('j')
            ->where('j.moduleSlugs LIKE :needle')
            ->setParameter('needle', '%"' . addcslashes($slug, '%_\\') . '"%')
            ->getQuery()->getResult();

        return array_values(array_filter(
            $candidates,
            static fn (Journey $j) => $j->containsSlug($slug),
        ));
    }
}
