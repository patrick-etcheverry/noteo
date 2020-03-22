<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\Evaluation;
use App\Entity\GroupeEtudiant;
use App\Entity\Partie;
use App\Entity\Points;
use App\Entity\Statut;


class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = \Faker\Factory::create('fr_FR');

        $nbDonnesTest = 15;


        //Admin Lié aux groupes
        $enseignant = new Enseignant();
        $enseignant->setPrenom('Patrick');
        $enseignant->setNom('Etcheverry');
        $enseignant->setEmail('patoche@iut.fr');
        $enseignant->setRoles(['ROLE_USER','ROLE_ADMIN']);
        $enseignant->setPassword('$2y$10$hq3YT8ne121.2/zAbw18OOtxM/Nh4ulNUvU.asGtTipYUSXimGow6');

        $manager->persist($enseignant);

        ////////////GROUPES//////////////

        ////////////ESPACE//////////////
        $espace = new GroupeEtudiant();
        $espace->setNom('Etudiants non affectés');
        $espace->setDescription('Tout les étudiants ayant été retirés d\'un groupe de haut niveau et ne faisant partie d\'aucun groupe');
        $espace->setEnseignant($enseignant);
        $espace->setEstEvaluable(false);
        $espace->setSlug($espace->slugify($espace->getNom()));

        ////////////RACINE//////////////
        $DUT = new GroupeEtudiant();
        $DUT->setNom('DUT Info');
        $DUT->setDescription('Tout les étudiants du DUT Informatique de l\'IUT');
        $DUT->setEnseignant($enseignant);
        $DUT->setEstEvaluable(false);
        $DUT->setSlug($DUT->slugify($DUT->getNom()));

        ////////////SEMESTRES//////////////
        $S1 = new GroupeEtudiant();
        $S1->setNom('S1');
        $S1->setDescription('Les etudiants du S1 du DUT Info');
        $S1->setParent($DUT);
        $S1->setEnseignant($enseignant);
        $S1->setEstEvaluable(true);
        $S1->setSlug($S1->slugify($S1->getNom()));


            ////////////TDs//////////////
            $S1TD1 = new GroupeEtudiant();
            $S1TD1->setNom('TD1');
            $S1TD1->setDescription('Les etudiants du TD1 du S1');
            $S1TD1->setParent($S1);
            $S1TD1->setEnseignant($enseignant);
            $S1TD1->setEstEvaluable(true);
            $S1TD1->setSlug($S1TD1->slugify($S1TD1->getNom()));


                ////////////TPs//////////////
                $S1TD1TP1 = new GroupeEtudiant();
                $S1TD1TP1->setNom('TP1');
                $S1TD1TP1->setDescription('Les etudiants du TP1 du TD1 du S1');
                $S1TD1TP1->setParent($S1TD1);
                $S1TD1TP1->setEnseignant($enseignant);
                $S1TD1TP1->setEstEvaluable(true);
                $S1TD1TP1->setSlug($S1TD1TP1->slugify($S1TD1TP1->getNom()));


                $S1TD1TP2 = new GroupeEtudiant();
                $S1TD1TP2->setNom('TP2');
                $S1TD1TP2->setDescription('Les etudiants du TP2 du TD1 du S1');
                $S1TD1TP2->setParent($S1TD1);
                $S1TD1TP2->setEnseignant($enseignant);
                $S1TD1TP2->setEstEvaluable(true);
                $S1TD1TP2->setSlug($S1TD1TP2->slugify($S1TD1TP2->getNom()));

            $S1TD2 = new GroupeEtudiant();
            $S1TD2->setNom('TD2');
            $S1TD2->setDescription('Les etudiants du TD2 du S1');
            $S1TD2->setParent($S1);
            $S1TD2->setEnseignant($enseignant);
            $S1TD2->setEstEvaluable(true);
            $S1TD2->setSlug($S1TD2->slugify($S1TD2->getNom()));

                ////////////TPs//////////////
                $S1TD2TP3 = new GroupeEtudiant();
                $S1TD2TP3->setNom('TP3');
                $S1TD2TP3->setDescription('Les etudiants du TP3 du TD2 du S1');
                $S1TD2TP3->setParent($S1TD2);
                $S1TD2TP3->setEnseignant($enseignant);
                $S1TD2TP3->setEstEvaluable(true);
                $S1TD2TP3->setSlug($S1TD2TP3->slugify($S1TD2TP3->getNom()));

                $S1TD2TP4 = new GroupeEtudiant();
                $S1TD2TP4->setNom('TP4');
                $S1TD2TP4->setDescription('Les etudiants du TP4 du TD2 du S1');
                $S1TD2TP4->setParent($S1TD2);
                $S1TD2TP4->setEnseignant($enseignant);
                $S1TD2TP4->setEstEvaluable(true);
                $S1TD2TP4->setSlug($S1TD2TP4->slugify($S1TD2TP4->getNom()));

            $S1TD3 = new GroupeEtudiant();
            $S1TD3->setNom('TD3');
            $S1TD3->setDescription('Les etudiants du TD3 du S1');
            $S1TD3->setParent($S1);
            $S1TD3->setEnseignant($enseignant);
            $S1TD3->setEstEvaluable(true);
            $S1TD3->setSlug($S1TD3->slugify($S1TD3->getNom()));

                ////////////TPs//////////////
                $S1TD3TP5 = new GroupeEtudiant();
                $S1TD3TP5->setNom('TP5');
                $S1TD3TP5->setDescription('Les etudiants du TP5 du TD3 du S1');
                $S1TD3TP5->setParent($S1TD3);
                $S1TD3TP5->setEnseignant($enseignant);
                $S1TD3TP5->setEstEvaluable(true);
                $S1TD3TP5->setSlug($S1TD3TP5->slugify($S1TD3TP5->getNom()));

        $S2 = new GroupeEtudiant();
        $S2->setNom('S2');
        $S2->setDescription('Les etudiants du S2 du DUT Info');
        $S2->setParent($DUT);
        $S2->setEnseignant($enseignant);
        $S2->setEstEvaluable(true);
        $S2->setSlug($S2->slugify($S2->getNom()));

                    ////////////TDs//////////////
                    $S2TD1 = new GroupeEtudiant();
                    $S2TD1->setNom('TD1');
                    $S2TD1->setDescription('Les etudiants du TD1 du S2');
                    $S2TD1->setParent($S2);
                    $S2TD1->setEnseignant($enseignant);
                    $S2TD1->setEstEvaluable(true);
                    $S2TD1->setSlug($S2TD1->slugify($S2TD1->getNom()));

                        ////////////TPs//////////////
                        $S2TD1TP1 = new GroupeEtudiant();
                        $S2TD1TP1->setNom('TP1');
                        $S2TD1TP1->setDescription('Les etudiants du TP1 du TD1 du S2');
                        $S2TD1TP1->setParent($S2TD1);
                        $S2TD1TP1->setEnseignant($enseignant);
                        $S2TD1TP1->setEstEvaluable(true);
                        $S2TD1TP1->setSlug($S2TD1TP1->slugify($S2TD1TP1->getNom()));

                        $S2TD1TP2 = new GroupeEtudiant();
                        $S2TD1TP2->setNom('TP2');
                        $S2TD1TP2->setDescription('Les etudiants du TP2 du TD1 du S2');
                        $S2TD1TP2->setParent($S2TD1);
                        $S2TD1TP2->setEnseignant($enseignant);
                        $S2TD1TP2->setEstEvaluable(true);
                        $S2TD1TP2->setSlug($S2TD1TP2->slugify($S2TD1TP2->getNom()));

                    $S2TD2 = new GroupeEtudiant();
                    $S2TD2->setNom('TD2');
                    $S2TD2->setDescription('Les etudiants du TD2 du S2');
                    $S2TD2->setParent($S2);
                    $S2TD2->setEnseignant($enseignant);
                    $S2TD2->setEstEvaluable(true);
                    $S2TD2->setSlug($S2TD2->slugify($S2TD2->getNom()));

                        ////////////TPs//////////////
                        $S2TD2TP3 = new GroupeEtudiant();
                        $S2TD2TP3->setNom('TP3');
                        $S2TD2TP3->setDescription('Les etudiants du TP3 du TD2 du S2');
                        $S2TD2TP3->setParent($S2TD2);
                        $S2TD2TP3->setEnseignant($enseignant);
                        $S2TD2TP3->setEstEvaluable(true);
                        $S2TD2TP3->setSlug($S2TD2TP3->slugify($S2TD2TP3->getNom()));

                        $S2TD2TP4 = new GroupeEtudiant();
                        $S2TD2TP4->setNom('TP4');
                        $S2TD2TP4->setDescription('Les etudiants du TP4 du TD2 du S2');
                        $S2TD2TP4->setParent($S2TD2);
                        $S2TD2TP4->setEnseignant($enseignant);
                        $S2TD2TP4->setEstEvaluable(true);
                        $S2TD2TP4->setSlug($S2TD2TP4->slugify($S2TD2TP4->getNom()));

                    $S2TD3 = new GroupeEtudiant();
                    $S2TD3->setNom('TD3');
                    $S2TD3->setDescription('Les etudiants du TD3 du S1');
                    $S2TD3->setParent($S2);
                    $S2TD3->setEnseignant($enseignant);
                    $S2TD3->setEstEvaluable(true);
                    $S2TD3->setSlug($S2TD3->slugify($S2TD3->getNom()));

                        ////////////TPs//////////////
                        $S2TD3TP5 = new GroupeEtudiant();
                        $S2TD3TP5->setNom('TP5');
                        $S2TD3TP5->setDescription('Les etudiants du TP5 du TD3 du S2');
                        $S2TD3TP5->setParent($S2TD3);
                        $S2TD3TP5->setEnseignant($enseignant);
                        $S2TD3TP5->setEstEvaluable(true);
                        $S2TD3TP5->setSlug($S2TD3TP5->slugify($S2TD3TP5->getNom()));

        //Enregistrement des groupes
        $manager->persist($espace);
        $manager->persist($S1);
        $manager->persist($S1TD1);
        $manager->persist($S1TD1TP1);
        $manager->persist($S1TD1TP2);
        $manager->persist($S1TD2);
        $manager->persist($S1TD2TP3);
        $manager->persist($S1TD2TP4);
        $manager->persist($S1TD3);
        $manager->persist($S1TD3TP5);

        $manager->persist($S2);
        $manager->persist($S2TD1);
        $manager->persist($S2TD1TP1);
        $manager->persist($S2TD1TP2);
        $manager->persist($S2TD2);
        $manager->persist($S2TD2TP3);
        $manager->persist($S2TD2TP4);
        $manager->persist($S2TD3);
        $manager->persist($S2TD3TP5);

        ////////////STATUTS//////////////
        $statut1 = new Statut();
        $statut1->setNom('Boursiers');
        $statut1->setDescription('Les étudiants les moins riches de la promotion S3');
        $statut1->setEnseignant($enseignant);

        $statut2 = new Statut();
        $statut2->setNom('Blonds');
        $statut2->setDescription('Les moins beaux hommes sur terre');
        $statut2->setEnseignant($enseignant);

        $manager->persist($statut1);
        $manager->persist($statut2);

        for ($i = 0; $i < $nbDonnesTest; $i++) {

          ////////////ENSEIGNANT//////////////
          $enseignant = new Enseignant();
          $enseignant->setPrenom($faker->firstNameMale);
          $enseignant->setNom($faker->lastName);
          $enseignant->setEmail($faker->email);
          $enseignant->setRoles(['ROLE_USER']);
          $enseignant->setPassword('$2y$10$hq3YT8ne121.2/zAbw18OOtxM/Nh4ulNUvU.asGtTipYUSXimGow6');

          ////////////ETUDIANTS//////////////
          $etudiant1 = new Etudiant();
          $etudiant1->setPrenom($faker->firstNameMale);
          $etudiant1->setNom($faker->lastName);
          $etudiant1->setMail($faker->email);
          $etudiant1->setEstDemissionaire(false);
          $etudiant1->addStatut($statut1);
          $etudiant1->addGroupe($DUT);

          ////////////EVALUATION//////////////
          $evaluation = new Evaluation();
          $evaluation->setNom($faker->fileExtension);
          $evaluation->setDate(new \DateTime());
          $evaluation->setEnseignant($enseignant);
          $evaluation->setGroupe($S1);

          ////////////PARTIES//////////////
          $partie1 = new Partie();
          $partie1->setIntitule($faker->creditCardType);
          $partie1->setBareme(10);
          $partie1->setEvaluation($evaluation);

          $partie2 = new Partie ();
          $partie2->setIntitule($faker->creditCardType);
          $partie2->setBareme(10);
          $partie2->setEvaluation($evaluation);

          ////////////POINTS//////////////
          $pointsEtud = new Points();
          $pointsEtud->setValeur($faker->randomDigit);
          $pointsEtud->setEtudiant($etudiant1);
          $pointsEtud->setPartie($partie1);

          $pointsEtud = new Points();
          $pointsEtud->setValeur($faker->randomDigit);
          $pointsEtud->setEtudiant($etudiant1);
          $pointsEtud->setPartie($partie2);

          ////////////ENREGISTREMENT DES DONNEES//////////////
          $manager->persist($enseignant);
          $manager->persist($etudiant1);
          $manager->persist($evaluation);
          $manager->persist($partie1);
          $manager->persist($partie2);
          $manager->persist($pointsEtud);

        }
        $manager->flush();
    }
}
