<?php

namespace App\Repository;

use App\Entity\GoldInRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GoldInRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method GoldInRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method GoldInRequest[]    findAll()
 * @method GoldInRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GoldInRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GoldInRequest::class);
    }

    // /**
    //  * @return GoldInRequest[] Returns an array of GoldInRequest objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GoldInRequest
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
