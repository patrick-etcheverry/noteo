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
        return $this->getEntityManager()->createQuery('
            SELECT p.valeur
            FROM App\Entity\Points p
            JOIN p.etudiant et
            JOIN p.partie pa
            JOIN pa.evaluation ev
            WHERE ev.id = :id
            AND et.estDemissionaire = 0
            ORDER BY p.valeur ASC
        ')
            ->setParameter('id', $id)
            ->execute();
    }

    public function findNotesAndEtudiantByEvaluation($evaluation)
    {
        return $this->getEntityManager()->createQuery('
            SELECT p, et, pa, ev
            FROM App\Entity\Points p
            JOIN p.etudiant et
            JOIN p.partie pa
            JOIN pa.evaluation ev
            WHERE ev = :param
            AND et.estDemissionaire = 0
        ')
            ->setParameter('param', $evaluation)
            ->execute();
    }

    /**
    * @return Points[] Returns an array of Points objects
    */

    public function findByPartie($id)
    {
        return $this->getEntityManager()->createQuery('
            SELECT p.valeur
            FROM App\Entity\Points p
            JOIN p.partie pa
            JOIN p.etudiant et
            WHERE pa.id = :id
            AND et.estDemissionnaire = 0
            ORDER BY p.valeur ASC
            ')
            ->setParameter('id', $id)
            ->execute();
    }

    public function findByGroupe($idEval, $idGroupe)
    {
        return $this->getEntityManager()->createQuery('
            SELECT p.valeur
            FROM App\Entity\Points p
            JOIN p.etudiant et
            JOIN et.groupes g
            JOIN p.partie pa
            JOIN pa.evaluation ev
            WHERE ev.id = :idE
            AND g.id = :idG
            AND et.estDemissionaire = 0
            ORDER BY p.valeur ASC
        ')
            ->setParameter('idG', $idGroupe)
            ->setParameter('idE', $idEval)
            ->execute();
    }

    /**
    * @return Points[] Returns an array of Points objects
    */

    public function findByStatut($idEval, $idStatut)
    {
        return $this->getEntityManager()->createQuery('
            SELECT p.valeur
            FROM App\Entity\Points p
            JOIN p.etudiant et
            JOIN et.statuts s
            JOIN p.partie pa
            JOIN pa.evaluation ev
            WHERE ev.id = :idE
            AND s.id = :idS
            AND et.estDemissionaire = 0
            ORDER BY p.valeur ASC
        ')
            ->setParameter('idS', $idStatut)
            ->setParameter('idE', $idEval)
            ->execute();

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
