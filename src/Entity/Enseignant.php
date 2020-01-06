<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EnseignantRepository")
 */
class Enseignant
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $prenom;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $mail;

    /**
     * @ORM\Column(type="boolean")
     */
    private $estAdmin;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\GroupeEtudiant", mappedBy="enseignant")
     */
    private $groupes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Evaluation", mappedBy="enseignant")
     */
    private $evaluations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Statut", mappedBy="enseignant", orphanRemoval=true)
     */
    private $statuts;

    public function __construct()
    {
        $this->groupes = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->statuts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getEstAdmin(): ?bool
    {
        return $this->estAdmin;
    }

    public function setEstAdmin(bool $estAdmin): self
    {
        $this->estAdmin = $estAdmin;

        return $this;
    }

    /**
     * @return Collection|GroupeEtudiant[]
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(GroupeEtudiant $groupe): self
    {
        if (!$this->groupes->contains($groupe)) {
            $this->groupes[] = $groupe;
            $groupe->setEnseignant($this);
        }

        return $this;
    }

    public function removeGroupe(GroupeEtudiant $groupe): self
    {
        if ($this->groupes->contains($groupe)) {
            $this->groupes->removeElement($groupe);
            // set the owning side to null (unless already changed)
            if ($groupe->getEnseignant() === $this) {
                $groupe->setEnseignant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Evaluation[]
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): self
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations[] = $evaluation;
            $evaluation->setEnseignant($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): self
    {
        if ($this->evaluations->contains($evaluation)) {
            $this->evaluations->removeElement($evaluation);
            // set the owning side to null (unless already changed)
            if ($evaluation->getEnseignant() === $this) {
                $evaluation->setEnseignant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Statut[]
     */
    public function getStatuts(): Collection
    {
        return $this->statuts;
    }

    public function addStatut(Statut $statut): self
    {
        if (!$this->statuts->contains($statut)) {
            $this->statuts[] = $statut;
            $statut->setEnseignant($this);
        }

        return $this;
    }

    public function removeStatut(Statut $statut): self
    {
        if ($this->statuts->contains($statut)) {
            $this->statuts->removeElement($statut);
            // set the owning side to null (unless already changed)
            if ($statut->getEnseignant() === $this) {
                $statut->setEnseignant(null);
            }
        }

        return $this;
    }
}
