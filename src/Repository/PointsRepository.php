<?php

namespace App\Repository;

use App\Entity\Points;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Points|null find($id, $lockMode = null, $lockVersion = null)
 * @method Points|null findOneBy(array $criteria, array $orderBy = null)
 * @method Points[]    findAll()
 * @method Points[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PointsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Points::class);
    }

    /**
    * @return Points[] Returns an array of Points objects
    */

    public function findByEvaluation($id)
    {
        return $this->createQueryBuilder('po')
            ->join('po.partie', 'pa')
            ->join('pa.evaluation', 'e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
    * @return Points[] Returns an array of Points objects
    */

    public function findByPartie($id)
    {
        return $this->createQueryBuilder('po')
            ->join('po.partie', 'pa')
            ->andWhere('pa.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
    * @return Points[] Returns an array of Points objects
    */

    public function findByGroupe($idEval, $idGroupe)
    {
        return $this->createQueryBuilder('po')
            ->join('po.etudiant', 'et')
            ->join('et.groupes', 'g')
            ->join('po.partie', 'pa')
            ->join('pa.evaluation', 'ev')
            ->andWhere('ev.id = :idE')
            ->andWhere('g.id = :idG')
            ->setParameter('idG', $idGroupe)
            ->setParameter('idE', $idEval)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
    * @return Points[] Returns an array of Points objects
    */

    public function findByStatut($idEval, $idStatut)
    {
        return $this->createQueryBuilder('po')
            ->join('po.etudiant', 'et')
            ->join('et.statuts', 's')
            ->join('po.partie', 'pa')
            ->join('pa.evaluation', 'ev')
            ->andWhere('ev.id = :idE')
            ->andWhere('s.id = :idS')
            ->setParameter('idS', $idStatut)
            ->setParameter('idE', $idEval)
            ->getQuery()
            ->getResult()
        ;
    }


    // /**
    //  * @return Points[] Returns an array of Points objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Points
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
