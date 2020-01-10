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
        $enseignant->setMail('patoche@iut.fr');
        $enseignant->setEstAdmin(true);

        $manager->persist($enseignant);

        ////////////GROUPES//////////////

        ////////////RACINE//////////////
        $DUT = new GroupeEtudiant();
        $DUT->setNom('DUT Info');
        $DUT->setDescription('Tout les étudiants du DUT Informatique de l\'IUT');
        $DUT->setEstEvaluable(false);
        $DUT->setEnseignant($enseignant);

        ////////////SEMESTRES//////////////
        $S1 = new GroupeEtudiant();
        $S1->setNom('S1');
        $S1->setDescription('Les etudiants du S1 du DUT Info');
        $S1->setEstEvaluable(true);
        $S1->setParent($DUT);
        $S1->setEnseignant($enseignant);

            ////////////TDs//////////////
            $S1TD1 = new GroupeEtudiant();
            $S1TD1->setNom('TD1');
            $S1TD1->setDescription('Les etudiants du TD1 du S1');
            $S1TD1->setEstEvaluable(true);
            $S1TD1->setParent($S1);
            $S1TD1->setEnseignant($enseignant);

                ////////////TPs//////////////
                $S1TD1TP1 = new GroupeEtudiant();
                $S1TD1TP1->setNom('TP1');
                $S1TD1TP1->setDescription('Les etudiants du TP1 du TD1 du S1');
                $S1TD1TP1->setEstEvaluable(true);
                $S1TD1TP1->setParent($S1TD1);
                $S1TD1TP1->setEnseignant($enseignant);

                $S1TD1TP2 = new GroupeEtudiant();
                $S1TD1TP2->setNom('TP2');
                $S1TD1TP2->setDescription('Les etudiants du TP2 du TD1 du S1');
                $S1TD1TP2->setEstEvaluable(true);
                $S1TD1TP2->setParent($S1TD1);
                $S1TD1TP2->setEnseignant($enseignant);

            $S1TD2 = new GroupeEtudiant();
            $S1TD2->setNom('TD2');
            $S1TD2->setDescription('Les etudiants du TD2 du S1');
            $S1TD2->setEstEvaluable(true);
            $S1TD2->setParent($S1);
            $S1TD2->setEnseignant($enseignant);

                ////////////TPs//////////////
                $S1TD2TP1 = new GroupeEtudiant();
                $S1TD2TP1->setNom('TP1');
                $S1TD2TP1->setDescription('Les etudiants du TP1 du TD2 du S1');
                $S1TD2TP1->setEstEvaluable(true);
                $S1TD2TP1->setParent($S1TD2);
                $S1TD2TP1->setEnseignant($enseignant);

                $S1TD2TP2 = new GroupeEtudiant();
                $S1TD2TP2->setNom('TP2');
                $S1TD2TP2->setDescription('Les etudiants du TP2 du TD2 du S1');
                $S1TD2TP2->setEstEvaluable(true);
                $S1TD2TP2->setParent($S1TD2);
                $S1TD2TP2->setEnseignant($enseignant);

            $S1TD3 = new GroupeEtudiant();
            $S1TD3->setNom('TD3');
            $S1TD3->setDescription('Les etudiants du TD3 du S1');
            $S1TD3->setEstEvaluable(true);
            $S1TD3->setParent($S1);
            $S1TD3->setEnseignant($enseignant);

                ////////////TPs//////////////
                $S1TD3TP1 = new GroupeEtudiant();
                $S1TD3TP1->setNom('TP1');
                $S1TD3TP1->setDescription('Les etudiants du TP1 du TD3 du S1');
                $S1TD3TP1->setEstEvaluable(true);
                $S1TD3TP1->setParent($S1TD3);
                $S1TD3TP1->setEnseignant($enseignant);

                $S1TD3TP2 = new GroupeEtudiant();
                $S1TD3TP2->setNom('TP2');
                $S1TD3TP2->setDescription('Les etudiants du TP2 du TD3 du S1');
                $S1TD3TP2->setEstEvaluable(true);
                $S1TD3TP2->setParent($S1TD3);
                $S1TD3TP2->setEnseignant($enseignant);


        $S2 = new GroupeEtudiant();
        $S2->setNom('S2');
        $S2->setDescription('Les etudiants du S2 du DUT Info');
        $S2->setEstEvaluable(true);
        $S2->setParent($DUT);
        $S2->setEnseignant($enseignant);

                    ////////////TDs//////////////
                    $S2TD1 = new GroupeEtudiant();
                    $S2TD1->setNom('TD1');
                    $S2TD1->setDescription('Les etudiants du TD1 du S2');
                    $S2TD1->setEstEvaluable(true);
                    $S2TD1->setParent($S2);
                    $S2TD1->setEnseignant($enseignant);

                        ////////////TPs//////////////
                        $S2TD1TP1 = new GroupeEtudiant();
                        $S2TD1TP1->setNom('TP1');
                        $S2TD1TP1->setDescription('Les etudiants du TP1 du TD1 du S2');
                        $S2TD1TP1->setEstEvaluable(true);
                        $S2TD1TP1->setParent($S2TD1);
                        $S2TD1TP1->setEnseignant($enseignant);

                        $S2TD1TP2 = new GroupeEtudiant();
                        $S2TD1TP2->setNom('TP2');
                        $S2TD1TP2->setDescription('Les etudiants du TP2 du TD1 du S2');
                        $S2TD1TP2->setEstEvaluable(true);
                        $S2TD1TP2->setParent($S2TD1);
                        $S2TD1TP2->setEnseignant($enseignant);

                    $S2TD2 = new GroupeEtudiant();
                    $S2TD2->setNom('TD2');
                    $S2TD2->setDescription('Les etudiants du TD2 du S2');
                    $S2TD2->setEstEvaluable(true);
                    $S2TD2->setParent($S2);
                    $S2TD2->setEnseignant($enseignant);

                        ////////////TPs//////////////
                        $S2TD2TP1 = new GroupeEtudiant();
                        $S2TD2TP1->setNom('TP1');
                        $S2TD2TP1->setDescription('Les etudiants du TP1 du TD2 du S2');
                        $S2TD2TP1->setEstEvaluable(true);
                        $S2TD2TP1->setParent($S2TD2);
                        $S2TD2TP1->setEnseignant($enseignant);

                        $S2TD2TP2 = new GroupeEtudiant();
                        $S2TD2TP2->setNom('TP2');
                        $S2TD2TP2->setDescription('Les etudiants du TP2 du TD2 du S2');
                        $S2TD2TP2->setEstEvaluable(true);
                        $S2TD2TP2->setParent($S2TD2);
                        $S2TD2TP2->setEnseignant($enseignant);

                    $S2TD3 = new GroupeEtudiant();
                    $S2TD3->setNom('TD3');
                    $S2TD3->setDescription('Les etudiants du TD3 du S1');
                    $S2TD3->setEstEvaluable(true);
                    $S2TD3->setParent($S2);
                    $S2TD3->setEnseignant($enseignant);

                        ////////////TPs//////////////
                        $S2TD3TP1 = new GroupeEtudiant();
                        $S2TD3TP1->setNom('TP1');
                        $S2TD3TP1->setDescription('Les etudiants du TP1 du TD3 du S2');
                        $S2TD3TP1->setEstEvaluable(true);
                        $S2TD3TP1->setParent($S2TD3);
                        $S2TD3TP1->setEnseignant($enseignant);

                        $S2TD3TP2 = new GroupeEtudiant();
                        $S2TD3TP2->setNom('TP2');
                        $S2TD3TP2->setDescription('Les etudiants du TP2 du TD3 du S2');
                        $S2TD3TP2->setEstEvaluable(true);
                        $S2TD3TP2->setParent($S2TD3);
                        $S2TD3TP2->setEnseignant($enseignant);

        $S3 = new GroupeEtudiant();
        $S3->setNom('S3');
        $S3->setDescription('Les etudiants du S3 du DUT Info');
        $S3->setEstEvaluable(true);
        $S3->setParent($DUT);
        $S3->setEnseignant($enseignant);


                            ////////////TDs//////////////
                            $S3TD1 = new GroupeEtudiant();
                            $S3TD1->setNom('TD1');
                            $S3TD1->setDescription('Les etudiants du TD1 du S3');
                            $S3TD1->setEstEvaluable(true);
                            $S3TD1->setParent($S3);
                            $S3TD1->setEnseignant($enseignant);

                                ////////////TPs//////////////
                                $S3TD1TP1 = new GroupeEtudiant();
                                $S3TD1TP1->setNom('TP1');
                                $S3TD1TP1->setDescription('Les etudiants du TP1 du TD1 du S3');
                                $S3TD1TP1->setEstEvaluable(true);
                                $S3TD1TP1->setParent($S3TD1);
                                $S3TD1TP1->setEnseignant($enseignant);

                                $S3TD1TP2 = new GroupeEtudiant();
                                $S3TD1TP2->setNom('TP2');
                                $S3TD1TP2->setDescription('Les etudiants du TP2 du TD1 du S3');
                                $S3TD1TP2->setEstEvaluable(true);
                                $S3TD1TP2->setParent($S3TD1);
                                $S3TD1TP2->setEnseignant($enseignant);

                            $S3TD2 = new GroupeEtudiant();
                            $S3TD2->setNom('TD2');
                            $S3TD2->setDescription('Les etudiants du TD2 du S3');
                            $S3TD2->setEstEvaluable(true);
                            $S3TD2->setParent($S3);
                            $S3TD2->setEnseignant($enseignant);

                                ////////////TPs//////////////
                                $S3TD2TP1 = new GroupeEtudiant();
                                $S3TD2TP1->setNom('TP1');
                                $S3TD2TP1->setDescription('Les etudiants du TP1 du TD2 du S3');
                                $S3TD2TP1->setEstEvaluable(true);
                                $S3TD2TP1->setParent($S3TD2);
                                $S3TD2TP1->setEnseignant($enseignant);

                                $S3TD2TP2 = new GroupeEtudiant();
                                $S3TD2TP2->setNom('TP2');
                                $S3TD2TP2->setDescription('Les etudiants du TP2 du TD2 du S3');
                                $S3TD2TP2->setEstEvaluable(true);
                                $S3TD2TP2->setParent($S3TD2);
                                $S3TD2TP2->setEnseignant($enseignant);

                            $S3TD3 = new GroupeEtudiant();
                            $S3TD3->setNom('TD3');
                            $S3TD3->setDescription('Les etudiants du TD3 du S3');
                            $S3TD3->setEstEvaluable(true);
                            $S3TD3->setParent($S3);
                            $S3TD3->setEnseignant($enseignant);

                                ////////////TPs//////////////
                                $S3TD3TP1 = new GroupeEtudiant();
                                $S3TD3TP1->setNom('TP1');
                                $S3TD3TP1->setDescription('Les etudiants du TP1 du TD3 du S3');
                                $S3TD3TP1->setEstEvaluable(true);
                                $S3TD3TP1->setParent($S3TD3);
                                $S3TD3TP1->setEnseignant($enseignant);

                                $S3TD3TP2 = new GroupeEtudiant();
                                $S3TD3TP2->setNom('TP2');
                                $S3TD3TP2->setDescription('Les etudiants du TP2 du TD3 du S3');
                                $S3TD3TP2->setEstEvaluable(true);
                                $S3TD3TP2->setParent($S3TD3);
                                $S3TD3TP2->setEnseignant($enseignant);


        $S4 = new GroupeEtudiant();
        $S4->setNom('S4');
        $S4->setDescription('Les etudiants du S4 du DUT Info');
        $S4->setEstEvaluable(true);
        $S4->setParent($DUT);
        $S4->setEnseignant($enseignant);

            ////////////TDs//////////////
            $IPI = new GroupeEtudiant();
            $IPI->setNom('IPI');
            $IPI->setDescription('Les etudiants du parcours IPI du S4');
            $IPI->setEstEvaluable(true);
            $IPI->setParent($DUT);
            $IPI->setEnseignant($enseignant);

                ////////////TPs//////////////
                $S4IPITP1 = new GroupeEtudiant();
                $S4IPITP1->setNom('TP1');
                $S4IPITP1->setDescription('Les etudiants du TP1 parcours IPI du S4');
                $S4IPITP1->setEstEvaluable(true);
                $S4IPITP1->setParent($IPI);
                $S4IPITP1->setEnseignant($enseignant);

                $S4IPITP2 = new GroupeEtudiant();
                $S4IPITP2->setNom('TP2');
                $S4IPITP2->setDescription('Les etudiants du TP2 parcours IPI du S4');
                $S4IPITP2->setEstEvaluable(true);
                $S4IPITP2->setParent($IPI);
                $S4IPITP2->setEnseignant($enseignant);

            $PEL = new GroupeEtudiant();
            $PEL->setNom('PEL');
            $PEL->setDescription('Les etudiants du parcours PEL du S4');
            $PEL->setEstEvaluable(true);
            $PEL->setParent($DUT);
            $PEL->setEnseignant($enseignant);

                ////////////TPs//////////////
                $S4PELTP1 = new GroupeEtudiant();
                $S4PELTP1->setNom('TP1');
                $S4PELTP1->setDescription('Les etudiants du TP1 parcours PEL du S4');
                $S4PELTP1->setEstEvaluable(true);
                $S4PELTP1->setParent($PEL);
                $S4PELTP1->setEnseignant($enseignant);

                $S4PELTP2 = new GroupeEtudiant();
                $S4PELTP2->setNom('TP2');
                $S4PELTP2->setDescription('Les etudiants du TP2 parcours PEL du S4');
                $S4PELTP2->setEstEvaluable(true);
                $S4PELTP2->setParent($PEL);
                $S4PELTP2->setEnseignant($enseignant);

                $S4PELTP3 = new GroupeEtudiant();
                $S4PELTP3->setNom('TP3');
                $S4PELTP3->setDescription('Les etudiants du TP3 parcours PEL du S4');
                $S4PELTP3->setEstEvaluable(true);
                $S4PELTP3->setParent($PEL);
                $S4PELTP3->setEnseignant($enseignant);

        //Enregistrement des groupes
        $manager->persist($S1);
        $manager->persist($S1TD1);
        $manager->persist($S1TD1TP1);
        $manager->persist($S1TD1TP2);
        $manager->persist($S1TD2);
        $manager->persist($S1TD2TP1);
        $manager->persist($S1TD2TP2);
        $manager->persist($S1TD3);
        $manager->persist($S1TD3TP1);
        $manager->persist($S1TD3TP2);

        $manager->persist($S2);
        $manager->persist($S2TD1);
        $manager->persist($S2TD1TP1);
        $manager->persist($S2TD1TP2);
        $manager->persist($S2TD2);
        $manager->persist($S2TD2TP1);
        $manager->persist($S2TD2TP2);
        $manager->persist($S2TD3);
        $manager->persist($S2TD3TP1);
        $manager->persist($S2TD3TP2);

        $manager->persist($S3);
        $manager->persist($S3TD1);
        $manager->persist($S3TD1TP1);
        $manager->persist($S3TD1TP2);
        $manager->persist($S3TD2);
        $manager->persist($S3TD2TP1);
        $manager->persist($S3TD2TP2);
        $manager->persist($S3TD3);
        $manager->persist($S3TD3TP1);
        $manager->persist($S3TD3TP2);

        $manager->persist($S4);
        $manager->persist($IPI);
        $manager->persist($S4IPITP1);
        $manager->persist($S4IPITP2);
        $manager->persist($PEL);
        $manager->persist($S4PELTP1);
        $manager->persist($S4PELTP2);
        $manager->persist($S4PELTP3);



        for ($i = 0; $i < $nbDonnesTest; $i++) {

          ////////////ENSEIGNANT//////////////
          $enseignant = new Enseignant();
          $enseignant->setPrenom($faker->firstNameMale);
          $enseignant->setNom($faker->lastName);
          $enseignant->setMail($faker->email);
          $enseignant->setEstAdmin($faker->boolean);

          ////////////STATUTS//////////////
          $statut1 = new Statut();
          $statut1->setNom($faker->jobTitle);
          $statut1->setDescription($faker->text(75));
          $statut1->setEnseignant($enseignant);

          $statut2 = new Statut();
          $statut2->setNom($faker->jobTitle);
          $statut2->setDescription($faker->text(75));
          $statut2->setEnseignant($enseignant);

          ////////////ETUDIANTS//////////////
          $etudiant1 = new Etudiant();
          $etudiant1->setPrenom($faker->firstNameMale);
          $etudiant1->setNom($faker->lastName);
          $etudiant1->setMail($faker->email);
          $etudiant1->setEstDemissionaire($faker->boolean);
          $etudiant1->addStatut($statut1);
          $etudiant1->addGroupe($S3TD3TP2);

          $etudiant2 = new Etudiant();
          $etudiant2->setPrenom($faker->firstNameMale);
          $etudiant2->setNom($faker->lastName);
          $etudiant2->setMail($faker->email);
          $etudiant2->setEstDemissionaire($faker->boolean);
          $etudiant2->addStatut($statut2);
          $etudiant2->addGroupe($S4PELTP1);

          ////////////EVALUATION//////////////
          $evaluation = new Evaluation();
          $evaluation->setNom($faker->fileExtension);
          $evaluation->setDate($faker->date($format = 'Y-m-d', $max = 'now'));
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
          $pointsEtud->setEtudiant($etudiant2);
          $pointsEtud->setPartie($partie2);

          ////////////ENREGISTREMENT DES DONNEES//////////////
          $manager->persist($enseignant);
          $manager->persist($statut1);
          $manager->persist($statut2);
          $manager->persist($etudiant1);
          $manager->persist($etudiant2);
          $manager->persist($evaluation);
          $manager->persist($partie1);
          $manager->persist($partie2);
          $manager->persist($pointsEtud);

        }
        $manager->flush();
    }
}
