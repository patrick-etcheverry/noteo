<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PartieRepository")
 */
class Partie
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $intitule;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\NotBlank
     */
    private $bareme;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Points", mappedBy="partie")
     */
    private $notes;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Evaluation", inversedBy="parties")
     */
    private $evaluation;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(?string $intitule): self
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getBareme(): ?float
    {
        return $this->bareme;
    }

    public function setBareme(?float $bareme): self
    {
        $this->bareme = $bareme;

        return $this;
    }

    /**
     * @return Collection|Points[]
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Points $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setPartie($this);
        }

        return $this;
    }

    public function removeNote(Points $note): self
    {
        if ($this->notes->contains($note)) {
            $this->notes->removeElement($note);
            // set the owning side to null (unless already changed)
            if ($note->getPartie() === $this) {
                $note->setPartie(null);
            }
        }

        return $this;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): self
    {
        $this->evaluation = $evaluation;

        return $this;
    }
}
