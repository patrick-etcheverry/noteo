<?php

namespace App\Repository;

use App\Entity\GroupeEtudiant;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method GroupeEtudiant|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupeEtudiant|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupeEtudiant[]    findAll()
 * @method GroupeEtudiant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupeEtudiantRepository extends NestedTreeRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupeEtudiant::class);
    }

    // /**
    //  * @return GroupeEtudiant[] Returns an array of GroupeEtudiant objects
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
    public function findOneBySomeField($value): ?GroupeEtudiant
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