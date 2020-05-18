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

    public function findByPartieAndByStudent($idPartie, $idEtudiant)
    {
        return $this->createQueryBuilder('n')
            ->join('n.partie', 'p')
            ->join('n.etudiant', 'e')
            ->andWhere('p.id = :idPartie')
            ->andWhere('e.id = :idEtudiant')
            ->setParameter('idPartie', $idPartie)
            ->setParameter('idEtudiant', $idEtudiant)
            ->getQuery()
            ->getSingleResult()
            ;
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
            AND p.valeur >= 0
            AND et.estDemissionaire = 0
        ')
            ->setParameter('param', $evaluation)
            ->execute();
    }

    /**
    * @return Points[] Returns an array of Points objects
    */

    public function findAllFromLowestParties($idEvaluation)
    {
        return $this->getEntityManager()->createQuery('
            SELECT p
            FROM App\Entity\Points p
            JOIN p.partie pa
            JOIN pa.evaluation ev
            JOIN p.etudiant et
            WHERE ev.id = :idEvaluation
            AND pa.rgt = pa.lft + 1
            ORDER BY et.id ASC, pa.lft ASC
            ')
            ->setParameter('idEvaluation', $idEvaluation)
            ->execute();
    }

    public function findByGroupe($slugEval, $slugGroupe)
    {
        return $this->getEntityManager()->createQuery('
            SELECT p.valeur
            FROM App\Entity\Points p
            JOIN p.etudiant et
            JOIN et.groupes g
            JOIN p.partie pa
            JOIN pa.evaluation ev
            WHERE ev.slug = :slugE
            AND g.slug = :slugG
            AND et.estDemissionaire = 0
            AND p.valeur >= 0
            ORDER BY p.valeur ASC
        ')
            ->setParameter('slugG', $slugGroupe)
            ->setParameter('slugE', $slugEval)
            ->execute();
    }

    public function findUniqueByGroupe($idEval, $idGroupe)
    {
        return $this->getEntityManager()->createQuery('
            SELECT DISTINCT p.valeur
            FROM App\Entity\Points p
            JOIN p.etudiant et
            JOIN et.groupes g
            JOIN p.partie pa
            JOIN pa.evaluation ev
            WHERE ev.id = :idE
            AND g.id = :idG
            AND et.estDemissionaire = 0
            AND p.valeur >= 0
            ORDER BY p.valeur DESC
        ')
            ->setParameter('idG', $idGroupe)
            ->setParameter('idE', $idEval)
            ->execute();
    }

    public function findByStatut($slugEval, $slugStatut)
    {
        return $this->getEntityManager()->createQuery('
            SELECT p.valeur
            FROM App\Entity\Points p
            JOIN p.etudiant et
            JOIN et.statuts s
            JOIN p.partie pa
            JOIN pa.evaluation ev
            WHERE ev.slug = :slugE
            AND s.slug = :slugS
            AND et.estDemissionaire = 0
            AND p.valeur >= 0
            ORDER BY p.valeur ASC
        ')
            ->setParameter('slugS', $slugStatut)
            ->setParameter('slugE', $slugEval)
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
