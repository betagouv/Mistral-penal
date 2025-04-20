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

use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Service\Encryption\EncryptionHelperTrait;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\AffairePersonneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [new Get(), new Put()], normalizationContext: ['groups' => ['read']])]
#[ORM\Table(schema: 'webapp', name: 'affaire_personne')]
#[ORM\Entity(repositoryClass: AffairePersonneRepository::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['affaire' => 'exact'])]
class AffairePersonne
{
    use EncryptionHelperTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $nomComplet = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $b1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $mineur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $dateDeferement = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $aj = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $isDefere = null;

    #[ORM\ManyToOne(inversedBy: 'affairePersonnes')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    private ?Affaire $affaire = null;

    #[ORM\ManyToOne()]
    #[Groups(["read"])]
    private ?StatutPersonne $statut = null;

    #[ORM\ManyToOne(inversedBy: 'affairePersonnes')]
    #[ORM\JoinColumn(onDelete:"CASCADE", nullable: false)]
    #[Groups(["read"])]
    private ?Personne $personne = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $categoriePenale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $avocat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $dup = null;

    #[ORM\ManyToOne]
    private ?ModePoursuite $modePoursuite = null;

    private ?Collection $affaireNatinfs = null;

    #[ORM\ManyToOne]
    private ?ModeConvocation $modeConvocation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $assisteDe = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dateConvocation = null;

    #[ORM\ManyToOne]
    private ?ModeComparution $modeComparution = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $natureJugement = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isVisio = false;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $debutVisio = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finVisio = null;

    #[ORM\ManyToMany(targetEntity: Renvoi::class, mappedBy: 'affairePersonnes')]
    #[ORM\JoinTable(schema: 'webapp', name: 'renvoi_affaire_personne')]
    private Collection $renvois;

    #[ORM\OneToMany(mappedBy: 'representant', targetEntity: RepresentantLegal::class)]
    private Collection $representes;

    #[ORM\OneToMany(mappedBy: 'represente', targetEntity: RepresentantLegal::class)]
    private Collection $representants;

    public function __construct()
    {
        $this->renvois = new ArrayCollection();
        $this->representes = new ArrayCollection();
        $this->representants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomComplet(): ?string
    {
        return $this->getDecryption('nomComplet', $this->nomComplet);
    }

    public function setNomComplet(?string $nomComplet): self
    {
        $this->setEncryption('nomComplet', $nomComplet);

        return $this;
    }

    public function getB1(): ?string
    {
        return $this->getDecryption('b1', $this->b1);
    }

    public function setB1(?string $b1): self
    {
        $this->setEncryption('b1', $b1);

        return $this;
    }

    public function getMineur(): ?string
    {
        return $this->getDecryption('mineur', $this->mineur);
    }

    public function setMineur(?string $mineur): self
    {
        $this->setEncryption('mineur', $mineur);

        return $this;
    }

    public function getDateDeferement(): ?\DateTimeInterface
    {
        return $this->getDecryption('dateDeferement', $this->dateDeferement);
    }

    public function setDateDeferement(?\DateTimeInterface $dateDeferement): self
    {
        $this->setEncryption('dateDeferement', $dateDeferement);

        return $this;
    }

    public function isAj(): ?bool
    {
        return $this->aj;
    }

    public function setAj(?bool $aj): self
    {
        $this->aj = $aj;

        return $this;
    }

    public function isIsDefere(): ?bool
    {
        return $this->isDefere;
    }

    public function setIsDefere(?bool $isDefere): self
    {
        $this->isDefere = $isDefere;

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

    public function getStatut(): ?StatutPersonne
    {
        return $this->statut;
    }

    public function setStatut(?StatutPersonne $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getPersonne(): ?Personne
    {
        return $this->personne;
    }

    public function setPersonne(?Personne $personne): self
    {
        $this->personne = $personne;

        return $this;
    }

    public function getCategoriePenale(): ?string
    {
        return $this->getDecryption('categoriePenale', $this->categoriePenale);
    }

    public function setCategoriePenale(?string $categoriePenale): self
    {
        $this->setEncryption('categoriePenale', $categoriePenale);

        return $this;
    }

    public function getAvocat(): ?string
    {
        return $this->getDecryption('avocat', $this->avocat);
    }

    public function setAvocat(?string $avocat): self
    {
        $this->setEncryption('avocat', $avocat);

        return $this;
    }

    public function getDup(): ?string
    {
        return $this->getDecryption('dup', $this->dup);
    }

    public function setDup(?string $dup): self
    {
        $this->setEncryption('dup', $dup);

        return $this;
    }

    public function getModePoursuite(): ?ModePoursuite
    {
        return $this->modePoursuite;
    }

    public function setModePoursuite(?ModePoursuite $modePoursuite): static
    {
        $this->modePoursuite = $modePoursuite;

        return $this;
    }

    public function setAffaireNatinfs(Collection $affaireNatinfs): self
    {
      /** do nothing */
      return $this;
    }

    public function getActiveAffaireNatinfs(): Collection
    {
        /** @var ?Affaire $affaire */
        $affaire = $this->getAffaire();
        /** @var Collection $affaireNatinfs */
        $affaireNatinfs = $affaire->getAffaireNatinfs();
        /** @var Personne $personne */
        $personne = $this->getPersonne();
        return $affaireNatinfs->filter(function(AffaireNatinf $affaireNatinf)use($personne) {
          $natinfPersonnes = $affaireNatinf->getPersonnes();
          foreach($natinfPersonnes as $natinfPersonne) {
            $lPersonne = $natinfPersonne->getPersonne();
            if($lPersonne == $personne)
              return(!$personne->isDisqualifie($affaireNatinf));
          }
          return false;
        });
    }

    public function getAffaireNatinfs(): Collection
    {
        /** @var ?Affaire $affaire */
        $affaire = $this->getAffaire();
        /** @var Collection $affaireNatinfs */
        $affaireNatinfs = $affaire->getAffaireNatinfs();
        /** @var Personne $personne */
        $personne = $this->getPersonne();
        return $affaireNatinfs->filter(function(AffaireNatinf $affaireNatinf)use($personne) {
          $natinfPersonnes = $affaireNatinf->getPersonnes();
          foreach($natinfPersonnes as $natinfPersonne) {
            $lPersonne = $natinfPersonne->getPersonne();
            if($lPersonne == $personne)
              return true;
          }
          return false;
        });
    }

    public function getModeConvocation(): ?ModeConvocation
    {
        return $this->modeConvocation;
    }

    public function setModeConvocation(?ModeConvocation $modeConvocation): static
    {
        $this->modeConvocation = $modeConvocation;

        return $this;
    }

    public function getAssisteDe(): ?string
    {
        return $this->getDecryption('assisteDe', $this->assisteDe);
    }

    public function setAssisteDe(?string $assisteDe): static
    {
        $this->setEncryption('assisteDe', $assisteDe);

        return $this;
    }

    public function getDateConvocation(): ?\DateTimeInterface
    {
        return $this->getDecryption('dateConvocation', $this->dateConvocation);
        return $this->dateConvocation;
    }

    public function setDateConvocation(?\DateTimeInterface $dateConvocation): static
    {
        $this->setEncryption('dateConvocation', $dateConvocation);

        return $this;
    }

    public function getModeComparution(): ?ModeComparution
    {
        return $this->modeComparution;
    }

    public function setModeComparution(?ModeComparution $modeComparution): static
    {
        $this->modeComparution = $modeComparution;

        return $this;
    }

    public function getNatureJugement(): ?string
    {
        return $this->getDecryption('natureJugement', $this->natureJugement);
    }

    public function setNatureJugement(?string $natureJugement): static
    {
        $this->setEncryption('natureJugement', $natureJugement);

        return $this;
    }

    public function isIsVisio(): ?bool
    {
        return $this->isVisio;
    }

    public function setIsVisio(?bool $isVisio): static
    {
        $this->isVisio = $isVisio;

        return $this;
    }

    public function getDebutVisio(): ?\DateTimeInterface
    {
        return $this->debutVisio;
    }

    public function setDebutVisio(?\DateTimeInterface $debutVisio): static
    {
        $this->debutVisio = $debutVisio;

        return $this;
    }

    public function getFinVisio(): ?\DateTimeInterface
    {
        return $this->finVisio;
    }

    public function setFinVisio(?\DateTimeInterface $finVisio): static
    {
        $this->finVisio = $finVisio;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getRenvois(): Collection
    {
        return $this->renvois;
    }

    public function addRenvoi(Renvoi $renvoi): self
    {
        if (!$this->renvois->contains($renvoi)) {
            $this->renvois->add($renvoi);
            $renvoi->addAffairePersonne($this);
        }

        return $this;
    }

    public function removeRenvoi(Renvoi $renvoi): self
    {
        if ($this->renvois->contains($renvoi)) {
            $this->renvois->removeElement($renvoi);
            $renvoi->removeAffairePersonne($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, RepresentantLegal>
     */
    public function getRepresentes(): Collection
    {
        return $this->representes;
    }

    public function addRepresente(RepresentantLegal $represente): static
    {
        if (!$this->representes->contains($represente)) {
            $this->representes->add($represente);
            $represente->setRepresentant($this);
        }

        return $this;
    }

    public function removeRepresente(RepresentantLegal $represente): static
    {
        if ($this->representes->removeElement($represente)) {
            // set the owning side to null (unless already changed)
            if ($represente->getRepresentant() === $this) {
                $represente->setRepresentant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RepresentantLegal>
     */
    public function getRepresentants(): Collection
    {
        return $this->representants;
    }

    public function addRepresentant(RepresentantLegal $representant): static
    {
        if (!$this->representants->contains($representant)) {
            $this->representants->add($representant);
            $representant->setRepresente($this);
        }

        return $this;
    }

    public function removeRepresentant(RepresentantLegal $representant): static
    {
        if ($this->representants->removeElement($representant)) {
            // set the owning side to null (unless already changed)
            if ($representant->getRepresente() === $this) {
                $representant->setRepresente(null);
            }
        }

        return $this;
    }

    public function getNomPourMotRapide(): array
    {
        $personne = $this->getPersonne();
        $statutPersonne = $this->getStatut();
        $id = $personne->getNom() ? $personne->getPrenom1().' '.strtoupper($personne->getNom()) : $this->getNomComplet();
        $statutLibelle = (null !== $statutPersonne) ? ' ('.$statutPersonne->getLibelle().')' : "";
        $statutCode = (null !== $statutPersonne) ? $statutPersonne->getCode() : "";
        $nomComplet = $id.$statutLibelle;
        return ['id' => $id, 'nom_complet' => $nomComplet, 'type' => strtolower($statutCode)];
    }
}
