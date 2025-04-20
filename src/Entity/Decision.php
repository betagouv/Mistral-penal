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

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\DecisionRepository;
use DateTime;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use phpDocumentor\Reflection\Types\Nullable;

#[ApiResource(operations: [new Get(), new Delete()], normalizationContext: ['groups' => ['read']])]
#[ORM\Entity(repositoryClass: DecisionRepository::class)]
#[ORM\Table(schema: 'webapp', name: 'decision')]
class Decision
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $peines = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Ignore]
    private ?\DateTimeInterface $date = null;

    #[Groups(["read"])]
    private ?string $dateStr = null;

    #[ORM\ManyToOne(targetEntity: Affaire::class,inversedBy: 'decisions')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Ignore]
    private ?Affaire $affaire;

    #[ORM\ManyToOne(targetEntity: DecisionPrevention::class)]
    #[Groups(["read"])]
    private ?DecisionPrevention $decisionPrevention;

    #[ORM\ManyToOne(targetEntity: DecisionSanction::class)]
    #[ORM\JoinColumn(name:"decision_sanction_id", referencedColumnName:"id", nullable:true)]
    #[Groups(["read"])]
    private ?DecisionSanction $decisionSanction = null;

    #[ORM\ManyToOne(targetEntity: ModulationPeine::class)]
    #[ORM\JoinColumn(name:"modulation_peine_id", referencedColumnName:"id", nullable:true)]
    #[Groups(["read"])]
    private ?ModulationPeine $modulationPeine = null;

    #[ORM\ManyToOne(targetEntity: AffairePersonne::class)]
    #[Groups(["read"])]
    private AffairePersonne $affairePersonne;

    #[ORM\ManyToMany(targetEntity: AffaireNatinf::class, inversedBy: 'decisions')]
    #[ORM\JoinTable(schema: 'webapp', name: 'decision_affaire_natinf')]
    #[Groups(["read"])]
    private Collection $affaireNatinfs;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $numeroMinute = null;

    public function __construct()
    {
        $this->affaireNatinfs = new ArrayCollection();
        $this->date = new DateTime();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeines(): ?string
    {
        return $this->peines;
    }

    public function setPeines(?string $peines): self
    {
        $this->peines = $peines;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function getDateStr(): ?string
    {
        if($this->date instanceof DateTime){
            return $this->date->format('d/m/y');
        }
        return "null";
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
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

    public function getDecisionPrevention(): ?DecisionPrevention
    {
        return $this->decisionPrevention;
    }

    public function setDecisionPrevention(?DecisionPrevention $decisionPrevention): self
    {
        $this->decisionPrevention = $decisionPrevention;

        return $this;
    }

    public function getDecisionSanction(): ?DecisionSanction
    {
        return $this->decisionSanction;
    }

    public function setDecisionSanction(?DecisionSanction $decisionSanction): self
    {
        $this->decisionSanction = $decisionSanction;

        return $this;
    }

    public function getModulationPeine(): ?ModulationPeine
    {
        return $this->modulationPeine;
    }

    public function setModulationPeine(?ModulationPeine $modulationPeine): self
    {
        $this->modulationPeine = $modulationPeine;

        return $this;
    }

    /**
     * @return AffairePersonne
     */
    public function getAffairePersonne(): AffairePersonne
    {
        return $this->affairePersonne;
    }

    public function setAffairePersonne(AffairePersonne $affairePersonne): self
    {
        $this->affairePersonne = $affairePersonne;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAffaireNatinfs(): Collection
    {
        return $this->affaireNatinfs;
    }

    public function addAffaireNatinf(AffaireNatinf $affaireNatinf): self
    {
        if (!$this->affaireNatinfs->contains($affaireNatinf)) {
            $this->affaireNatinfs->add($affaireNatinf);
        }

        return $this;
    }

    public function removeAffaireNatinf(AffaireNatinf $affaireNatinf): self
    {
        if ($this->affaireNatinfs->contains($affaireNatinf)) {
            $this->affaireNatinfs->removeElement($affaireNatinf);
        }

        return $this;
    }

    public function getNumeroMinute(): ?string
    {
        return $this->numeroMinute;
    }

    public function setNumeroMinute(?string $numeroMinute): self
    {
        if ($numeroMinute === "") {
            $numeroMinute = null;
        }

        $this->numeroMinute = $numeroMinute;

        return $this;
    }
}
