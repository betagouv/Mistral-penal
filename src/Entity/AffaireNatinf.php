<?php
/**
 * MIT License
 * 
 * Copyright (c) 2025 Mistral pénal - Incubateur du Minitère de la Justice
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use App\Controller\AffaireNatinf\PostDisqualificationRequalification;
use App\Controller\AffaireNatinf\PostRevertDisqualification;
use App\Entity\NatinfPersonne;
use App\Repository\AffaireNatinfRepository;
use App\Repository\HorodatageFaitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [new Get()],
    normalizationContext: ['groups' => ['read']]
)]
#[ORM\Table(schema: 'webapp', name: 'affaire_natinf')]
#[ORM\Entity(repositoryClass: AffaireNatinfRepository::class)]
class AffaireNatinf
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete:"CASCADE", nullable: false)]
    #[Groups(["read"])]
    private ?Natinf $natinf = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idKsp = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["read"])]
    private ?string $lieu = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Commune $commune = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(["read"])]
    private ?HorodatageFait $debut = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(["read"])]
    private ?HorodatageFait $fin = null;

    #[ORM\ManyToOne(inversedBy: 'affaireNatinfs')]
    #[ORM\JoinColumn(onDelete:"CASCADE", nullable: false)]
    private ?Affaire $affaire = null;

    #[ORM\OneToMany(targetEntity: NatinfPersonne::class, mappedBy: 'affaireNatinf')]
    #[Groups(["read"])]
    private Collection $personnes;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $qualificationDeveloppee = null;

    #[ORM\ManyToMany(targetEntity: Decision::class, mappedBy: 'affaireNatinfs')]
    private Collection $decisions;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $duplicateParent = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $duplicateRoot = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $nature = null;

    public function __construct()
    {
      $this->personnes = new ArrayCollection();
      $this->decisions = new ArrayCollection();
    }


    /**
     * @return Collection<int, NatinfPersonne>
     */
    public function getPersonnes(): Collection
    {
        return $this->personnes;
    }

    public function setPersonnes(array $personnes): void {
        $this->personnes = new ArrayCollection($personnes);
    }

    public function addPersonne(NatinfPersonne $personne): self
    {
        if (!$this->personnes->contains($personne)) {
            $this->personnes->add($personne);
        }

        return $this;
    }

    public function removePersonne(NatinfPersonne $personne): self
    {
        if ($this->personnes->removeElement($personne)) {
            $personne->setAffaireNatinf(null);
        }

        return $this;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAffaire(): ?Affaire
    {
        return $this->affaire;
    }

    public function setAffaire(?Affaire $affaire): self
    {
        $this->affaire = $affaire;

        return $this;
    }

    public function getNatinf(): ?Natinf
    {
        return $this->natinf;
    }

    public function setNatinf(?Natinf $natinf): self
    {
        $this->natinf = $natinf;

        return $this;
    }

    public function getIdKsp(): ?string
    {
        return $this->idKsp;
    }

    public function setIdKsp(?string $idKsp): self
    {
        $this->idKsp = $idKsp;

        return $this;
    }

    public function getLieu(): string
    {
        return $this->lieu??"";
    }

    public function setLieu(?string $lieu): self
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getCommune(): ?Commune
    {
        return $this->commune;
    }

    public function setCommune(?Commune $commune): self
    {
        $this->commune = $commune;

        return $this;
    }

    public function getDebut(): ?HorodatageFait
    {
        return $this->debut;
    }

    public function setDebut(?HorodatageFait $debut): self
    {
        $this->debut = $debut;

        return $this;
    }

    public function getFin(): ?HorodatageFait
    {
        return $this->fin;
    }

    public function setFin(?HorodatageFait $fin): self
    {
        $this->fin = $fin;

        return $this;
    }

    public function getLocalizationPlaintext(): ?string
    {
      $lieu = $this->getLieu();
      $commune = $this->getCommune() ? $this->getCommune()->getLibelle() : null;
      $tab=[];
      if(!empty($commune))
        $tab[]=$commune;
      if(!empty($lieu))
        $tab[]=$lieu;
      if($tab)
        return implode(' - ', $tab);
      return null;
    }

    public function getQualificationDeveloppee(): ?string
    {
        return $this->qualificationDeveloppee;
    }

    public function setQualificationDeveloppee(?string $qualificationDeveloppee): static
    {
        $this->qualificationDeveloppee = $qualificationDeveloppee;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getDecisions(): Collection
    {
        return $this->decisions;
    }

    public function addDecision(Decision $decision): self
    {
        if (!$this->decisions->contains($decision)) {
            $this->decisions->add($decision);
            $decision->addAffaireNatinf($this);
        }

        return $this;
    }

    public function removeDecision(Decision $decision): self
    {
        if ($this->decisions->contains($decision)) {
            $this->decisions->removeElement($decision);
            $decision->removeAffaireNatinf($this);
        }

        return $this;
    }

    public function getDuplicateParent(): ?self
    {
        return $this->duplicateParent;
    }

    public function setDuplicateParent(?self $duplicateParent): static
    {
        $this->duplicateParent = $duplicateParent;

        return $this;
    }

    public function getDuplicateRoot(): ?self
    {
        return $this->duplicateRoot;
    }

    public function setDuplicateRoot(?self $duplicateRoot): static
    {
        $this->duplicateRoot = $duplicateRoot;

        return $this;
    }

    /**
     * Récupération de la natinf de disqual/requal
     *
     * @return ?Natinf
     */
    public function getNatinfDisqual(): ?Natinf
    {
        $root = $this->getDuplicateRoot();
        if(null !== $root && $root->getNatinf() != $this->getNatinf())
          return $root->getNatinf();
        return null;
    }

    public function getNatinfDisqualPlaintext(): ?string
    {
        return $this->getNatinfDisqual() ? $this->getNatinfDisqual()->getPlaintext() : null;
    }

    public function getCommunePlaintext(): ?string
    {
        return $this->getCommune() ? $this->getCommune()->getPlaintext() : null;
    }

    public function getNatinfPlaintext(): ?string
    {
        return $this->getNatinf() ? $this->getNatinf()->getPlaintext() : null;
    }
    /**
     * Récupération de la date de disqual/requal
     *
     * @return ?HorodatageFait
     */
    public function getDebutDisqual(): ?HorodatageFait
    {
        $root = $this->getDuplicateRoot();
        if(
          (null !== $root)
          &&
          (false === HorodatageFaitRepository::isSame($root->getDebut(),$this->getDebut()))
        )
          return $root->getDebut();
        return null;
    }

    /**
     * Récupération de la date de fin de disqual/requal
     *
     * @return ?HorodatageFait
     */
    public function getFinDisqual(): ?HorodatageFait
    {
        $root = $this->getDuplicateRoot();

        if(
          (null !== $root) &&
          (false === HorodatageFaitRepository::isSame($root->getFin(),$this->getFin()))
        )
          return $root->getFin();
        return null;
    }

    public function getDatePlaintext(): string
    {
      $tab=[];
      if($this->getDebut())
        $tab[]=$this->getDebut()->getPlaintext();
      if($this->getFin() && $this->getFin()->getDate())
        $tab[]=$this->getFin()->getPlaintext();
      return implode(" ", $tab);
    }

    public function getDateDisqualPlaintext(): ?string
    {
      $tab=[];
      if($this->hasDateDisqual())
      {
        if($this->hasDateDebutDisqual())
          $tab[]=$this->getDebutDisqual()->getPlaintext();
        else
          $tab[]=$this->getDebut()->getPlaintext();

        if($this->getFinDisqual())
          $tab[]=$this->getFinDisqual()->getPlaintext();
        return implode(" ",$tab);
      }
      return null;
    }
    public function hasDateFinDisqual(): bool
    {
      $root = $this->getDuplicateRoot();
      return
        (null!==$this->getFinDisqual())
        ||
        (
          (null !== $root)
          &&
          (false === HorodatageFaitRepository::isSame($root->getFin(),$this->getFin()))
        )
      ;
    }
    public function hasDateDebutDisqual(): bool
    {
      $root = $this->getDuplicateRoot();
      return (null!==$this->getDebutDisqual());
    }
    public function hasDateDisqual(): bool
    {
      return
        $this->hasDateDebutDisqual() ||
        $this->hasDateFinDisqual()
      ;
    }

    public function getNature(): ?string
    {
        return $this->nature;
    }

    public function setNature(?string $nature): static
    {
        $this->nature = $nature;

        return $this;
    }

    public function getNatinfPersonneIds(): array
    {
        $tab=[];
        foreach($this->getPersonnes() as $natinfPersonne)
          $tab[]=$natinfPersonne->getId();
        return $tab;
    }

    public function getPersonneIds(): array
    {
        $tab=[];
        foreach($this->getPersonnes() as $natinfPersonne)
          $tab[]=$natinfPersonne->getPersonne()->getId();
        return $tab;
    }

    /**
     * Verrouillage de la mise à jour d'une AffaireNatinf si une décision
     * existe pour cet objet
     */
    public function isLocked(): bool
    {
        return ($this->getDecisions()->count()>0);
    }

    public function isDisqualRequal(): bool
    {
        return
          (true===$this->hasDateDisqual())||(null !== $this->getNatinfDisqual())
        ;
    }
}
