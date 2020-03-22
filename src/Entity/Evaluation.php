<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EvaluationRepository")
 */
class Evaluation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\Length(max=50)
     * @Assert\NotBlank
     */
    private $nom;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank
     * @Assert\Date
     */
    private $date;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Partie", mappedBy="evaluation")
     */
    private $parties;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Enseignant", inversedBy="evaluations")
     */
    private $enseignant;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\GroupeEtudiant", inversedBy="evaluations")
     */
    private $groupe;

    /**
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Slug(fields={"nom"})
     */
    private $slug;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function __construct()
    {
        $this->parties = new ArrayCollection();
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

    public function getDate()
    {
      if ($this->date != null) {
          return $this->date->format('d/m/Y');
      }

    }
    public function getDateUnformatted()
    {
      return $this->date;
    }

    public function setDate($date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection|Partie[]
     */
    public function getParties(): Collection
    {
        return $this->parties;
    }

    public function addPartie(Partie $partie): self
    {
        if (!$this->parties->contains($partie)) {
            $this->parties[] = $partie;
            $partie->setEvaluation($this);
        }

        return $this;
    }

    public function removePartie(Partie $partie): self
    {
        if ($this->parties->contains($partie)) {
            $this->parties->removeElement($partie);
            // set the owning side to null (unless already changed)
            if ($partie->getEvaluation() === $this) {
                $partie->setEvaluation(null);
            }
        }

        return $this;
    }

    public function getEnseignant(): ?Enseignant
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Enseignant $enseignant): self
    {
        $this->enseignant = $enseignant;

        return $this;
    }

    public function getGroupe(): ?GroupeEtudiant
    {
        return $this->groupe;
    }

    public function setGroupe(?GroupeEtudiant $groupe): self
    {
        $this->groupe = $groupe;

        return $this;
    }
}
