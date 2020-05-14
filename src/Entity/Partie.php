<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
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

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Partie")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Partie", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Partie", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    public function getLvl() {
        return $this->lvl;
    }

    public function getLft() {
        return $this->lft;
    }
    public function getRgt() {
        return $this->rgt;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function setParent(Partie $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren() {
        return $this->children;
    }

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
