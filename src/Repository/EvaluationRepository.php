<?php

namespace App\Repository;

use App\Entity\Evaluation;
use App\Entity\Points;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Evaluation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evaluation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evaluation[]    findAll()
 * @method Evaluation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    /**
    * @return Evaluation[] Returns an array of Evaluation objects
    */

    public function findOtherEvaluationsWithGradesAndCreatorAndGroup($enseignant)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('p')
            ->addSelect('en')
            ->addSelect('g')
            ->addSelect('n')
            ->leftJoin('e.parties', 'p')
            ->leftJoin('p.notes', 'n')
            ->join('e.groupe', 'g')
            ->join('e.enseignant', 'en')
            ->andWhere('e.enseignant != :enseignant')
            ->andWhere('n.valeur >= 0')
            ->setParameter('enseignant', $enseignant)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findMyEvaluationsWithGradesAndCreatorAndGroup($enseignant)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('p')
            ->addSelect('en')
            ->addSelect('g')
            ->addSelect('n')
            ->leftJoin('e.parties', 'p')
            ->leftJoin('p.notes', 'n')
            ->join('e.groupe', 'g')
            ->join('e.enseignant', 'en')
            ->andWhere('e.enseignant = :enseignant')
            ->andWhere('n.valeur >= 0')
            ->setParameter('enseignant', $enseignant)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllWithOnePart()
    {
        return $this->getEntityManager()->createQuery('
            SELECT e, g, en
            FROM App\Entity\Evaluation e
            JOIN e.groupe g
            JOIN e.enseignant en
            JOIN e.parties pa
            GROUP BY e,g, en
            HAVING count(pa.id) = 1
        ')
        ->execute();
    }

    public function findAllWithSeveralParts()
    {
        return $this->getEntityManager()->createQuery('
            SELECT e, g, en
            FROM App\Entity\Evaluation e
            JOIN e.groupe g
            JOIN e.enseignant en
            JOIN e.parties pa
            GROUP BY e,g, en
            HAVING count(pa.id) > 1
        ')
            ->execute();
    }

    public function findAllByStatut($idStatut) {
        return $this->getEntityManager()->createQuery('
            SELECT e
            FROM App\Entity\Evaluation e
            JOIN e.groupe g
            JOIN g.etudiants et
            JOIN et.statuts s
            WHERE s.id = :idStatut
        ')
            ->setParameter('idStatut', $idStatut)
            ->execute();
    }


    // /**
    //  * @return Stage[] Returns an array of Stage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Stage
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */



}
