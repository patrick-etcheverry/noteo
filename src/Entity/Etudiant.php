<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EtudiantRepository")
 */
class Etudiant
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25)
     * @Assert\NotBlanck
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=25)
     * @Assert\NotBlanck
     */
    private $prenom;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlanck
     */
    private $mail;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotBlanck
     */
    private $estDemissionaire;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\GroupeEtudiant", inversedBy="etudiants")
     */
    private $groupes;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Statut", mappedBy="etudiants")
     */
    private $statuts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Points", mappedBy="etudiant")
     */
    private $points;


    public function __construct()
    {
        $this->groupes = new ArrayCollection();
        $this->statuts = new ArrayCollection();
        $this->points = new ArrayCollection();
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

    public function getEstDemissionaire(): ?bool
    {
        return $this->estDemissionaire;
    }

    public function setEstDemissionaire(bool $estDemissionaire): self
    {
        $this->estDemissionaire = $estDemissionaire;

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
        }

        return $this;
    }

    public function removeGroupe(GroupeEtudiant $groupe): self
    {
        if ($this->groupes->contains($groupe)) {
            $this->groupes->removeElement($groupe);
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
            $statut->addEtudiant($this);
        }

        return $this;
    }

    public function removeStatut(Statut $statut): self
    {
        if ($this->statuts->contains($statut)) {
            $this->statuts->removeElement($statut);
            $statut->removeEtudiant($this);
        }

        return $this;
    }

    /**
     * @return Collection|Points[]
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    public function addPoint(Points $point): self
    {
        if (!$this->points->contains($point)) {
            $this->points[] = $point;
            $point->setEtudiant($this);
        }

        return $this;
    }

    public function removePoint(Points $point): self
    {
        if ($this->points->contains($point)) {
            $this->points->removeElement($point);
            // set the owning side to null (unless already changed)
            if ($point->getEtudiant() === $this) {
                $point->setEtudiant(null);
            }
        }

        return $this;
    }

}
