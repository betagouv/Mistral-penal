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
use App\Repository\PersonneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [new Get(), new Put()], normalizationContext: ['groups' => ['read']])]
#[ORM\Table(schema: 'webapp', name: 'personne')]
#[ORM\Entity(repositoryClass: PersonneRepository::class)]
class Personne
{
    use EncryptionHelperTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $prenom1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $nomUsage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $prenom2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $prenom3 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $codeBarreFnaeg = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $portable = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $courriel = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Commune $communeNaissance = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Pays $paysNaissance = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Langue $langueParlee = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["read"])]
    private ?string $idKsp = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Nationalite $nationalite = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Nationalite $nationalite2 = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?CategoriePenale $categoriePenale = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Civilite $civilite = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?AntecedentJudiciaire $antecedentJudiciaire = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?SansDomicile $sansDomicile = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(["read"])]
    private ?Parente $pere = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(["read"])]
    private ?Parente $mere = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $xSeDisant = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $declarationAdresse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $dateNaissance = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $dateDeces = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $dateDeclarationAdresse = null;

    #[ORM\OneToOne(inversedBy: 'personne', cascade: ['persist', 'remove'])]
    #[Groups(["read"])]
    private ?Adresse $adresse = null;

    #[ORM\OneToMany(mappedBy: 'personne', targetEntity: AffairePersonne::class)]
    private Collection $affairePersonnes;

    #[ORM\ManyToOne]
    private ?SituationFamilliale $situationFamilliale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $isDecede;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSansDomicile = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sigle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $enseigne = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $sirenSiret = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $raisonSociale = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(["read"])]
    private bool $isPersonneMorale = false;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?FormeJuridique $formeJuridique = null;

    #[Groups(["read"])]
    private string $smallNomComplet="";

    public function __construct(?EntityManagerInterface $em=null)
    {
        $this->setNom("");
        $this->setPrenom1("");
        $this->affairePersonnes = new ArrayCollection();
        $this->setIsDecede(false);
        if(null !== $em) {
          $paysNaissance = $em
            ->getRepository(Pays::class)
            ->findOneBy(['mnemo' => 'FR'])
          ;
          $this->setPaysNaissance($paysNaissance);
          $nationalite = $em
            ->getRepository(Nationalite::class)
            ->findOneBy(['mnemo' => 'fra'])
          ;
          $this->setNationalite($nationalite);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->getDecryption('nom', $this->nom);
    }

    public function setNom(?string $nom): self
    {
        $this->setEncryption('nom', $nom);

        return $this;
    }

    public function getPrenom1(): ?string
    {
        return $this->getDecryption('prenom1', $this->prenom1);
    }

    public function setPrenom1(?string $prenom1): self
    {
        $this->setEncryption('prenom1', $prenom1);

        return $this;
    }

    public function getNomUsage(): ?string
    {
        return $this->getDecryption('nomUsage', $this->nomUsage);
    }

    public function setNomUsage(?string $nomUsage): self
    {
        $this->setEncryption('nomUsage', $nomUsage);

        return $this;
    }

    public function getPrenom2(): ?string
    {
        return $this->getDecryption('prenom2', $this->prenom2);
    }

    public function setPrenom2(?string $prenom2): self
    {
        $this->setEncryption('prenom2', $prenom2);

        return $this;
    }

    public function getPrenom3(): ?string
    {
        return $this->getDecryption('prenom3', $this->prenom3);
    }

    public function setPrenom3(?string $prenom3): self
    {
        $this->setEncryption('prenom3', $prenom3);

        return $this;
    }

    public function getCodeBarreFnaeg(): ?string
    {
        return $this->getDecryption('codeBarreFnaeg', $this->codeBarreFnaeg);
    }

    public function setCodeBarreFnaeg(?string $codeBarreFnaeg): self
    {
        $this->setEncryption('codeBarreFnaeg', $codeBarreFnaeg);

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->getDecryption('telephone', $this->telephone);
    }

    public function setTelephone(?string $telephone): self
    {
        $this->setEncryption('telephone', $telephone);

        return $this;
    }

    public function getPortable(): ?string
    {
        return $this->getDecryption('portable', $this->portable);
    }

    public function setPortable(?string $portable): self
    {
        $this->setEncryption('portable', $portable);

        return $this;
    }

    public function getCourriel(): ?string
    {
        return $this->getDecryption('courriel', $this->courriel);
    }

    public function setCourriel(?string $courriel): self
    {
        $this->setEncryption('courriel', $courriel);

        return $this;
    }

    public function getCommuneNaissance(): ?Commune
    {
        return $this->communeNaissance;
    }

    public function setCommuneNaissance(?Commune $communeNaissance): self
    {
        $this->communeNaissance = $communeNaissance;

        return $this;
    }

    public function getPaysNaissance(): ?Pays
    {
        return $this->paysNaissance;
    }

    public function setPaysNaissance(?Pays $paysNaissance): self
    {
        $this->paysNaissance = $paysNaissance;

        return $this;
    }

    public function getLangueParlee(): ?Langue
    {
        return $this->langueParlee;
    }

    public function setLangueParlee(?Langue $langueParlee): self
    {
        $this->langueParlee = $langueParlee;

        return $this;
    }

    public function getIdKsp(): ?string
    {
        return $this->idKsp;
    }

    public function setIdKsp(string $idKsp): self
    {
        $this->idKsp = $idKsp;

        return $this;
    }

    public function getNationalite(): ?Nationalite
    {
        return $this->nationalite;
    }

    public function setNationalite(?Nationalite $nationalite): self
    {
        $this->nationalite = $nationalite;

        return $this;
    }

    public function getNationalite2(): ?Nationalite
    {
        return $this->nationalite2;
    }

    public function setNationalite2(?Nationalite $nationalite2): self
    {
        $this->nationalite2 = $nationalite2;

        return $this;
    }

    public function getCategoriePenale(): ?CategoriePenale
    {
        return $this->categoriePenale;
    }

    public function setCategoriePenale(?CategoriePenale $categoriePenale): self
    {
        $this->categoriePenale = $categoriePenale;

        return $this;
    }

    public function getCivilite(): ?Civilite
    {
        return $this->civilite;
    }

    public function setCivilite(?Civilite $civilite): self
    {
        $this->civilite = $civilite;

        return $this;
    }

    public function getAntecedentJudiciaire(): ?AntecedentJudiciaire
    {
        return $this->antecedentJudiciaire;
    }

    public function setAntecedentJudiciaire(?AntecedentJudiciaire $antecedentJudiciaire): self
    {
        $this->antecedentJudiciaire = $antecedentJudiciaire;

        return $this;
    }

    public function getSansDomicile(): ?SansDomicile
    {
        return $this->sansDomicile;
    }

    public function setSansDomicile(?SansDomicile $sansDomicile): self
    {
        $this->sansDomicile = $sansDomicile;

        return $this;
    }

    public function getPere(): ?Parente
    {
        return $this->pere;
    }

    public function setPere(?Parente $pere): self
    {
        $this->pere = $pere;

        return $this;
    }

    public function getMere(): ?Parente
    {
        return $this->mere;
    }

    public function setMere(?Parente $mere): self
    {
        $this->mere = $mere;

        return $this;
    }

    public function isXSeDisant(): ?bool
    {
        return $this->getDecryption('xSeDisant', $this->xSeDisant);
    }

    public function setXSeDisant(?bool $xSeDisant): self
    {
        $this->setEncryption('xSeDisant', $xSeDisant);

        return $this;
    }

    public function isDeclarationAdresse(): ?bool
    {
        return $this->declarationAdresse;
    }

    public function setDeclarationAdresse(?bool $declarationAdresse): self
    {
        $this->declarationAdresse = $declarationAdresse;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->getDecryption('dateNaissance', $this->dateNaissance);
    }
    /**
     * @author      yanroussel
     *              passage de la date de naissance en mixed à cause
     *              de la dénormalisation API PLATFORM qui le détecte en
     *              champs "string"
     */
    public function setDateNaissance(mixed $dateNaissance): self
    {
        if((null===$dateNaissance)||($dateNaissance instanceof \DateTimeInterface))
          $this->setEncryption('dateNaissance', $dateNaissance);
        elseif(!empty($dateNaissance)&&preg_match("/(\d{4})[-](\d{2})[-](\d{2})/i",$dateNaissance)) {
          $dateNaissance = new \DateTime($dateNaissance);
          $this->setEncryption('dateNaissance', $dateNaissance);
        }
        elseif(""===$dateNaissance) {
          $dateNaissance=null;
          $this->setEncryption('dateNaissance', $dateNaissance);
        }
        return $this;
    }

    public function getDateDeces(): ?\DateTimeInterface
    {
        return $this->getDecryption('dateDeces', $this->dateDeces);
    }

    public function setDateDeces(?\DateTimeInterface $dateDeces): self
    {
        $this->setEncryption('dateDeces', $dateDeces);

        return $this;
    }

    public function getDateDeclarationAdresse(): ?\DateTimeInterface
    {
        return $this->getDecryption('dateDeclarationAdresse', $this->dateDeclarationAdresse);
    }

    public function setDateDeclarationAdresse(?\DateTimeInterface $dateDeclarationAdresse): self
    {
        $this->setEncryption('dateDeclarationAdresse', $dateDeclarationAdresse);

        return $this;
    }

    public function getAdresse(): ?Adresse
    {
        return $this->adresse;
    }

    public function setAdresse(?Adresse $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * @return Collection<int, AffairePersonne>
     */
    public function getAffairePersonnes(): Collection
    {
        return $this->affairePersonnes;
    }

    public function addAffairePersonne(AffairePersonne $affairePersonne): self
    {
        if (!$this->affairePersonnes->contains($affairePersonne)) {
            $this->affairePersonnes->add($affairePersonne);
            $affairePersonne->setPersonne($this);
        }

        return $this;
    }

    public function removeAffairePersonne(AffairePersonne $affairePersonne): self
    {
        if ($this->affairePersonnes->removeElement($affairePersonne)) {
            // set the owning side to null (unless already changed)
            if ($affairePersonne->getPersonne() === $this) {
                $affairePersonne->setPersonne(null);
            }
        }

        return $this;
    }

    public function getSituationFamilliale(): ?SituationFamilliale
    {
        return $this->situationFamilliale;
    }

    public function setSituationFamilliale(?SituationFamilliale $situationFamilliale): static
    {
        $this->situationFamilliale = $situationFamilliale;

        return $this;
    }

    public function isIsDecede(): ?bool
    {
        return $this->getDecryption('isDecede', $this->isDecede);
    }

    public function setIsDecede(?bool $isDecede): static
    {
        $this->setEncryption('isDecede', $isDecede);

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function isIsSansDomicile(): ?bool
    {
        return $this->isSansDomicile;
    }

    public function setIsSansDomicile(?bool $isSansDomicile): static
    {
        $this->isSansDomicile = $isSansDomicile;

        return $this;
    }

    public function getSmallNomComplet(): string
    {
        if(true === $this->isPersonneMorale())
          return trim(mb_strtoupper($this->getRaisonSociale()));

        $prenom = ucfirst(mb_strtolower($this->getPrenom1()));
        return trim(mb_strtoupper($this->getNom()).' '.$prenom);
    }

    public function getNomComplet(): string
    {
      if(true === $this->isPersonneMorale())
        return mb_strtoupper($this->getRaisonSociale());

      $prenoms = [];
      if(!empty($this->getPrenom1()))
        $prenoms[]=$this->getPrenom1();
      if(!empty($this->getPrenom2()))
        $prenoms[]=$this->getPrenom2();
      if(!empty($this->getPrenom3()))
        $prenoms[]=$this->getPrenom3();
      foreach($prenoms as $index => $prenom)
        $prenoms[$index] = ucfirst(mb_strtolower($prenom));
      return mb_strtoupper($this->getNom()).' '.implode(" , ", $prenoms);
    }

    public function isDisqualifie(AffaireNatinf $affaireNatinf): ?bool
    {
      foreach($affaireNatinf->getPersonnes() as $natinfPersonne) {
        if($natinfPersonne->getPersonne() == $this) {
          return $natinfPersonne->isDisqualifie();
        }
      }
      return true;
    }

    public function getSigle(): ?string
    {
        return $this->getDecryption('sigle', $this->sigle);
    }

    public function setSigle(?string $sigle): static
    {
        $this->setEncryption('sigle', $sigle);

        return $this;
    }

    public function getEnseigne(): ?string
    {
        return $this->getDecryption('enseigne', $this->enseigne);
    }

    public function setEnseigne(?string $enseigne): static
    {
        $this->setEncryption('enseigne', $enseigne);

        return $this;
    }

    public function getSirenSiret(): ?string
    {
        return $this->getDecryption('sirenSiret', $this->sirenSiret);
    }

    public function setSirenSiret(?string $sirenSiret): static
    {
        $this->setEncryption('sirenSiret', $sirenSiret);

        return $this;
    }

    public function getRaisonSociale(): ?string
    {
        return $this->getDecryption('raisonSociale', $this->raisonSociale);
    }

    public function setRaisonSociale(?string $raisonSociale): static
    {
        $this->setEncryption('raisonSociale', $raisonSociale);

        return $this;
    }

    public function isPersonneMorale(): bool
    {
        return $this->isPersonneMorale;
    }

    public function setIsPersonneMorale(bool $isPersonneMorale): static
    {
        $this->isPersonneMorale = $isPersonneMorale;

        return $this;
    }

    public function getFormeJuridique(): ?FormeJuridique
    {
        return $this->formeJuridique;
    }

    public function setFormeJuridique(?FormeJuridique $formeJuridique): static
    {
        $this->formeJuridique = $formeJuridique;

        return $this;
    }
}
