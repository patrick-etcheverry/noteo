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

          ////////////GROUPE//////////////
          $groupe = new GroupeEtudiant();
          $groupe->setNom($faker->text($maxNbChars = 15));
          $groupe->setDescription($faker->text(75));
          $groupe->setEstEvaluable($faker->boolean);
          $groupe->setEnseignant($enseignant);

          $groupe2 = new GroupeEtudiant();
          $groupe2->setNom($faker->text($maxNbChars = 15));
          $groupe2->setDescription($faker->text(75));
          $groupe2->setEstEvaluable($faker->boolean);
          $groupe2->setEnseignant($enseignant);
          $groupe2->setParent($groupe);

          $groupe3 = new GroupeEtudiant();
          $groupe3->setNom($faker->text($maxNbChars = 15));
          $groupe3->setDescription($faker->text(75));
          $groupe3->setEstEvaluable($faker->boolean);
          $groupe3->setEnseignant($enseignant);
          $groupe3->setParent($groupe2);

          $groupe4 = new GroupeEtudiant();
          $groupe4->setNom($faker->text($maxNbChars = 15));
          $groupe4->setDescription($faker->text(75));
          $groupe4->setEstEvaluable($faker->boolean);
          $groupe4->setEnseignant($enseignant);
          $groupe4->setParent($groupe);

          ////////////ETUDIANTS//////////////
          $etudiant1 = new Etudiant();
          $etudiant1->setPrenom($faker->firstNameMale);
          $etudiant1->setNom($faker->lastName);
          $etudiant1->setMail($faker->email);
          $etudiant1->setEstDemissionaire($faker->boolean);
          $etudiant1->addStatut($statut1);
          $etudiant1->addGroupe($groupe);

          $etudiant2 = new Etudiant();
          $etudiant2->setPrenom($faker->firstNameMale);
          $etudiant2->setNom($faker->lastName);
          $etudiant2->setMail($faker->email);
          $etudiant2->setEstDemissionaire($faker->boolean);
          $etudiant2->addStatut($statut2);
          $etudiant2->addGroupe($groupe);


          ////////////EVALUATION//////////////
          $evaluation = new Evaluation();
          $evaluation->setNom($faker->fileExtension);
          $evaluation->setDate($faker->date($format = 'Y-m-d', $max = 'now'));
          $evaluation->setEnseignant($enseignant);
          $evaluation->setGroupe($groupe2);

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
          $manager->persist($groupe);
          $manager->persist($groupe2);
          $manager->persist($groupe3);
          $manager->persist($groupe4);
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
