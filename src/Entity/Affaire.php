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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use App\Service\Encryption\EncryptionHelperTrait;
use App\Entity\Audience;
use App\Entity\StatutPersonne;
use App\Controller\Affaire\GetMotRapide;
use App\Controller\Affaire\PostAjoutVictime;
use App\Controller\Affaire\DeleteVictime;
use App\Repository\AffaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new Get(
            controller: GetMotRapide::class,
            uriTemplate: '/affaires/v1/{id}/mot_rapide'
        ), 
        new GetCollection(
            controller: PostAjoutVictime::class,
            uriTemplate: '/affaires/v1/{id}/ajout-victime'
        )
    ],
    normalizationContext: ['groups' => ['read']]
)]
#[ORM\Table(schema: 'webapp', name: 'affaire')]
#[ORM\Entity(repositoryClass: AffaireRepository::class)]
class Affaire
{
    use SortableTrait;
    use EncryptionHelperTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idKsp = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["read"])]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["read"])]
    private ?string $numeroParquet = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $numeroCabinet = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $nature = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $nombrePrevenuConvoque = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $nombreDetenuConvoque = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $duree = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $identifiantJustice = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $parquetierEnCharge = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $serviceEnCharge = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $typeInfraction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $emetteur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $acteSaisine = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $nombreVehicule = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $scelle = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $scelleAgrasc = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $origine = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $isJIRS = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $isPlainteEnLigne = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $isAccesPnat = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $isAccesPnf = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $isPoleInstruction = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?bool $isEurojust = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $dateSaisine = null;

    #[ORM\ManyToOne(inversedBy: 'affaires')]
    #[ORM\JoinColumn(onDelete:"CASCADE", nullable: false)]
    private ?Audience $audience = null;

    #[ORM\ManyToMany(targetEntity: Nataff::class, mappedBy: 'affaires')]
    #[Groups(["read"])]
    private Collection $nataffs;

    #[ORM\OneToMany(mappedBy: 'affaire', targetEntity: AffairePersonne::class)]
    #[Groups(["read"])]
    private Collection $affairePersonnes;

    #[ORM\OneToMany(mappedBy: 'affaire', targetEntity: AffaireNatinf::class)]
    #[Groups(["read"])]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private Collection $affaireNatinfs;

    #[ORM\ManyToMany(targetEntity: Intervenant::class, mappedBy: 'affaires')]
    #[Groups(["read"])]
    private Collection $intervenants;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDernierImport = null;

    #[ORM\OneToMany(mappedBy: 'affaire', targetEntity: NoteAudience::class)]
    private Collection $noteAudiences;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $president = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $ministerePublic = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $Assesseur1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $assesseur2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $greffier = null;

    #[Groups(["read"])]
    private $juges = null;

    #[Groups(["read"])]
    private $prevenus = null;

    #[Groups(["read"])]
    private $victimes = null;

    #[ORM\OneToMany(targetEntity: Renvoi::class, mappedBy: 'affaire')]
    private Collection $renvois;

    #[ORM\OneToMany(targetEntity: Decision::class, mappedBy: 'affaire')]
    private Collection $decisions;

    #[Groups(["read"])]
    private ?string $numeroDossier = null;

    public function __construct()
    {
        $this->nataffs = new ArrayCollection();
        $this->affairePersonnes = new ArrayCollection();
        $this->affaireNatinfs = new ArrayCollection();
        $this->intervenants = new ArrayCollection();
        $this->noteAudiences = new ArrayCollection();
        $this->renvois = new ArrayCollection();
        $this->decisions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->getDecryption('type', $this->type);
    }

    public function setType(string $type): self
    {
        $this->setEncryption('type', $type);

        return $this;
    }

    public function getNumeroParquet(): ?string
    {
        return $this->getDecryption('numeroParquet', $this->numeroParquet);
    }

    public function setNumeroParquet(string $numeroParquet): self
    {
        $this->setEncryption('numeroParquet', $numeroParquet);

        return $this;
    }

    public function getNumeroCabinet(): ?string
    {
        return $this->getDecryption('numeroCabinet', $this->numeroCabinet);
    }

    public function setNumeroCabinet(?string $numeroCabinet): self
    {
        $this->setEncryption('numeroCabinet', $numeroCabinet);

        return $this;
    }

    public function getNature(): ?string
    {
        return $this->getDecryption('nature', $this->nature);
    }

    public function setNature(?string $nature): self
    {
        $this->setEncryption('nature', $nature);

        return $this;
    }

    public function getNombrePrevenuConvoque(): ?int
    {
        return $this->getDecryption('nombrePrevenuConvoque', $this->nombrePrevenuConvoque);
    }

    public function setNombrePrevenuConvoque(?int $nombrePrevenuConvoque): self
    {
        $this->setEncryption('nombrePrevenuConvoque', $nombrePrevenuConvoque);

        return $this;
    }

    public function getNombreDetenuConvoque(): ?int
    {
        return $this->getDecryption('nombreDetenuConvoque', $this->nombreDetenuConvoque);
    }

    public function setNombreDetenuConvoque(?int $nombreDetenuConvoque): self
    {
        $this->setEncryption('nombreDetenuConvoque', $nombreDetenuConvoque);

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->getDecryption('duree', $this->duree);
    }

    public function setDuree(?int $duree): self
    {
        $this->setEncryption('duree', $duree);

        return $this;
    }

    public function getIdentifiantJustice(): ?string
    {
        return $this->getDecryption('identifiantJustice', $this->identifiantJustice);
    }

    public function setIdentifiantJustice(?string $identifiantJustice): self
    {
        $this->setEncryption('identifiantJustice', $identifiantJustice);

        return $this;
    }

    public function getParquetierEnCharge(): ?string
    {
        return $this->getDecryption('parquetierEnCharge', $this->parquetierEnCharge);
    }

    public function setParquetierEnCharge(?string $parquetierEnCharge): self
    {
        $this->setEncryption('parquetierEnCharge', $parquetierEnCharge);

        return $this;
    }

    public function getServiceEnCharge(): ?string
    {
        return $this->getDecryption('serviceEnCharge', $this->serviceEnCharge);
    }

    public function setServiceEnCharge(?string $serviceEnCharge): self
    {
        $this->setEncryption('serviceEnCharge', $serviceEnCharge);

        return $this;
    }

    public function getTypeInfraction(): ?string
    {
        return $this->getDecryption('typeInfraction', $this->typeInfraction);
    }

    public function setTypeInfraction(?string $typeInfraction): self
    {
        $this->setEncryption('typeInfraction', $typeInfraction);

        return $this;
    }

    public function getEmetteur(): ?string
    {
        return $this->getDecryption('emetteur', $this->emetteur);
    }

    public function setEmetteur(?string $emetteur): self
    {
        $this->setEncryption('emetteur', $emetteur);

        return $this;
    }

    public function getActeSaisine(): ?string
    {
        return $this->getDecryption('acteSaisine', $this->acteSaisine);
    }

    public function setActeSaisine(?string $acteSaisine): self
    {
        $this->setEncryption('acteSaisine', $acteSaisine);

        return $this;
    }

    public function getNombreVehicule(): ?int
    {
        return $this->getDecryption('nombreVehicule', $this->nombreVehicule);
    }

    public function setNombreVehicule(?int $nombreVehicule): self
    {
        $this->setEncryption('nombreVehicule', $nombreVehicule);

        return $this;
    }

    public function isScelle(): ?bool
    {
        return $this->scelle;
    }

    public function setScelle(?bool $scelle): self
    {
        $this->scelle = $scelle;

        return $this;
    }

    public function isScelleAgrasc(): ?bool
    {
        return $this->scelleAgrasc;
    }

    public function setScelleAgrasc(?bool $scelleAgrasc): self
    {
        $this->scelleAgrasc = $scelleAgrasc;

        return $this;
    }

    public function getOrigine(): ?string
    {
        return $this->getDecryption('origine', $this->origine);
    }

    public function setOrigine(?string $origine): self
    {
        $this->setEncryption('origine', $origine);

        return $this;
    }

    public function isIsJIRS(): ?bool
    {
        return $this->isJIRS;
    }

    public function setIsJIRS(?bool $isJIRS): self
    {
        $this->isJIRS = $isJIRS;

        return $this;
    }

    public function isIsPlainteEnLigne(): ?bool
    {
        return $this->isPlainteEnLigne;
    }

    public function setIsPlainteEnLigne(?bool $isPlainteEnLigne): self
    {
        $this->isPlainteEnLigne = $isPlainteEnLigne;

        return $this;
    }

    public function isIsAccesPnat(): ?bool
    {
        return $this->getDecryption('isAccesPnat', $this->isAccesPnat);
    }

    public function setIsAccesPnat(?bool $isAccesPnat): self
    {
        $this->setEncryption('isAccesPnat', $isAccesPnat);

        return $this;
    }

    public function isIsAccesPnf(): ?bool
    {
        return $this->isAccesPnf;
    }

    public function setIsAccesPnf(?bool $isAccesPnf): self
    {
        $this->isAccesPnf = $isAccesPnf;

        return $this;
    }

    public function isIsPoleInstruction(): ?bool
    {
        return $this->isPoleInstruction;
    }

    public function setIsPoleInstruction(?bool $isPoleInstruction): self
    {
        $this->isPoleInstruction = $isPoleInstruction;

        return $this;
    }

    public function isIsEurojust(): ?bool
    {
        return $this->isEurojust;
    }

    public function setIsEurojust(?bool $isEurojust): self
    {
        $this->isEurojust = $isEurojust;

        return $this;
    }

    public function getDateSaisine(): ?\DateTimeInterface
    {
        return $this->getDecryption('dateSaisine', $this->dateSaisine);
    }

    public function setDateSaisine(?\DateTimeInterface $dateSaisine): self
    {
        $this->setEncryption('dateSaisine', $dateSaisine);

        return $this;
    }

    public function getAudience(): ?Audience
    {
        return $this->audience;
    }

    public function setAudience(?Audience $audience): self
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * @return Collection<int, Nataff>
     */
    public function getNataffs(): Collection
    {
        return $this->nataffs;
    }

    public function setNataffs(array $nataffs): void {
        $this->nataffs = new ArrayCollection($nataffs);
    }

    public function addNataff(Nataff $nataff): self
    {
        if (!$this->nataffs->contains($nataff)) {
            $this->nataffs->add($nataff);
            $nataff->addAffaire($this);
        }

        return $this;
    }

    public function removeNataff(Nataff $nataff): self
    {
        if ($this->nataffs->removeElement($nataff)) {
            $nataff->removeAffaire($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, AffairePersonne>
     */
    public function getAffairePersonnes(): Collection
    {
        return $this->affairePersonnes;
    }

    public function setAffairePersonnes(array $affairePersonnes): void {
        $this->affairePersonnes = new ArrayCollection($affairePersonnes);
    }

    public function reloadNomComplets(): void
    {
        $affairePersonnes = $this->getAffairePersonnes();
        foreach($affairePersonnes as $affairePersonne) {
          $affairePersonne->reloadNomComplet();
        }
    }

    public function addAffairePersonne(AffairePersonne $affairePersonne): self
    {
        if (!$this->affairePersonnes->contains($affairePersonne)) {
            $this->affairePersonnes->add($affairePersonne);
            $affairePersonne->setAffaire($this);
        }

        return $this;
    }

    public function removeAffairePersonne(AffairePersonne $affairePersonne): self
    {
        if ($this->affairePersonnes->removeElement($affairePersonne)) {
            // set the owning side to null (unless already changed)
            if ($affairePersonne->getAffaire() === $this) {
                $affairePersonne->setAffaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AffaireNatinf>
     */
    public function getAffaireNatinfs(): Collection
    {
        return $this->affaireNatinfs;
    }

    public function setAffaireNatinfs(array $affaireNatinfs): void {
        $this->affaireNatinfs = new ArrayCollection($affaireNatinfs);
    }

    public function addAffaireNatinf(AffaireNatinf $affaireNatinf): self
    {
        if (!$this->affaireNatinfs->contains($affaireNatinf)) {
            $this->affaireNatinfs->add($affaireNatinf);
            $affaireNatinf->setAffaire($this);
        }

        return $this;
    }

    public function removeAffaireNatinf(AffaireNatinf $affaireNatinf): self
    {
        if ($this->affaireNatinfs->removeElement($affaireNatinf)) {
            // set the owning side to null (unless already changed)
            if ($affaireNatinf->getAffaire() === $this) {
                $affaireNatinf->setAffaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Intervenant>
     */
    public function getIntervenants(): Collection
    {
        return $this->intervenants;
    }

    public function setIntervenants(array $intervenants) {
        $this->intervenants = new ArrayCollection($intervenants);
    }

    public function addIntervenant(Intervenant $intervenant): self
    {
        if (!$this->intervenants->contains($intervenant)) {
            $this->intervenants->add($intervenant);
            $this->setIntervenant($intervenant);
        }

        return $this;
    }

    public function removeIntervenant(Intervenant $intervenant): self
    {
        $this->intervenants->removeElement($intervenant);

        return $this;
    }

    public function setIntervenant(Intervenant $intervenant):void
    {
        $nom = $intervenant->getNomComplet();
        switch($intervenant->getRole()){
            case Audience::ROLE_PRESIDENT:
                if (!$this->getPresident()){
                    $this->setPresident($nom);
                }
            break;
            case Audience::ROLE_MINISTERE:
                if (!$this->getMinisterePublic()){
                    $this->setMinisterePublic($nom);
                }
            break;
            case Audience::ROLE_ASSESSEUR_1:
                if (!$this->getAssesseur1()){
                    $this->setAssesseur1($nom);
                }
            break;
            case Audience::ROLE_ASSESSEUR_2:
                if (!$this->getAssesseur2()){
                    $this->setAssesseur2($nom);
                }
            break;
            case Audience::ROLE_GREFFE:
                if (!$this->getGreffier()){
                    $this->setGreffier($nom);
                }
            break;
            default :
                //
            break;
        }
    }

    public function getIntervenantNomComplet(string $role) {
        switch($role) {
            case Audience::ROLE_PRESIDENT:
                return $this->getPresident();
            case Audience::ROLE_MINISTERE:
                return $this->getMinisterePublic();
            case Audience::ROLE_ASSESSEUR_1:
                return $this->getAssesseur1();
            case Audience::ROLE_ASSESSEUR_2:
                return $this->getAssesseur2();
            case Audience::ROLE_GREFFE:
                return $this->getGreffier();
            default:
                return "";
        }
    }

    public function getDateDernierImport(): ?\DateTimeInterface
    {
        return $this->dateDernierImport;
    }

    public function setDateDernierImport(?\DateTimeInterface $dateDernierImport): self
    {
        $this->dateDernierImport = $dateDernierImport;

        return $this;
    }

    /**
     * @return Collection<int, NoteAudience>
     */
    public function getNoteAudiences(): Collection
    {
        return $this->noteAudiences;
    }

    public function setNoteAudiences(array $noteAudiences): void {
        $this->noteAudiences = new ArrayCollection($noteAudiences);
    }

    public function addNoteAudience(NoteAudience $noteAudience): self
    {
        if (!$this->noteAudiences->contains($noteAudience)) {
            $this->noteAudiences->add($noteAudience);
            $noteAudience->setAffaire($this);
        }

        return $this;
    }

    public function removeNoteAudience(NoteAudience $noteAudience): self
    {
        if ($this->noteAudiences->removeElement($noteAudience)) {
            // set the owning side to null (unless already changed)
            if ($noteAudience->getAffaire() === $this) {
                $noteAudience->setAffaire(null);
            }
        }

        return $this;
    }

    public function getLastNoteAudience(): NoteAudience
    {
       /** @var Collection $noteAudiences */
       $noteAudiences = $this->noteAudiences;
       if($noteAudiences->count())
          return $noteAudiences->last();
       return new NoteAudience();
    }

    public function getAffairePersonnesByStatutCodes(array $statutCodes=[]): array
    {
      $output = [];
      /** @var AffairePersonne $affairePersonne */
      foreach($this->getAffairePersonnes() as $affairePersonne) {
        /** @var ?StatutPersonne $statutPersonne */
        $statutPersonne = $affairePersonne->getStatut();
        if($statutPersonne && in_array($statutPersonne->getCode(), $statutCodes )) {
          $output[$affairePersonne->getId()]=$affairePersonne;
        }
      }
      return $output;
    }

    public function getAffairePersonneByPersonne(Personne $personne): ?AffairePersonne
    {
      /** @var AffairePersonne $affairePersonne */
      foreach($this->getAffairePersonnes() as $affairePersonne)
        if($affairePersonne->getPersonne() == $personne)
          return $affairePersonne;
      return null;
    }

    /**
     * Récupération des victimes d'une affaire
     *
     * @return array<int, AffairePersonne>
     */
    public function getVictimes(): array
    {
      return $this->getAffairePersonnesByStatutCodes([
        StatutPersonne::CODE_VICTIME,
        StatutPersonne::CODE_PARTIE_CIVILE,
        StatutPersonne::CODE_PARTIE_CIVILE_RL,
        StatutPersonne::CODE_PARTIE_CIVILE_ABUSIVE,
        StatutPersonne::CODE_PARTIE_CIVILE_OPPOSANT,
      ]);
    }

    /**
     * Récupération des jugés d'une affaire (la distinction victime/mec disparait au jugement)
     *
     * @return array<int, AffairePersonne>
     */
    public function getJuges(): array
    {
      return $this->getAffairePersonnesByStatutCodes([StatutPersonne::CODE_JUGE]);
    }
    /**
     * Récupération des prévenus d'une affaire
     *
     * @return array<int, AffairePersonne>
     */
    public function getPrevenus(): array
    {
      return $this->getAffairePersonnesByStatutCodes([
        StatutPersonne::CODE_PREVENU,
        StatutPersonne::CODE_MIS_EN_CAUSE,
      ]);
    }

    public function getPresident(): ?string
    {
        return $this->getDecryption('president', $this->president);
    }

    public function setPresident(?string $president): static
    {
        $this->setEncryption('president', $president);

        return $this;
    }

    public function getMinisterePublic(): ?string
    {
        return $this->getDecryption('ministerePublic', $this->ministerePublic);
    }

    public function setMinisterePublic(?string $ministerePublic): static
    {
        $this->setEncryption('ministerePublic', $ministerePublic);

        return $this;
    }

    public function getAssesseur1(): ?string
    {
        return $this->getDecryption('Assesseur1', $this->Assesseur1);
    }

    public function setAssesseur1(?string $Assesseur1): static
    {
        $this->setEncryption('Assesseur1', $Assesseur1);

        return $this;
    }

    public function getAssesseur2(): ?string
    {
        return $this->getDecryption('assesseur2', $this->assesseur2);
    }

    public function setAssesseur2(?string $assesseur2): static
    {
        $this->setEncryption('assesseur2', $assesseur2);

        return $this;
    }

    public function getGreffier(): ?string
    {
        return $this->getDecryption('greffier', $this->greffier);
    }

    public function setGreffier(?string $greffier): static
    {
        $this->setEncryption('greffier', $greffier);

        return $this;
    }

    public function getRenvois(): Collection
    {
        return $this->renvois;
    }

    public function setRenvois(array $renvois): void {
        $this->renvois = new ArrayCollection($renvois);
    }

    public function addRenvoi(Renvoi $renvoi): self
    {
        if (!$this->renvois->contains($renvoi)) {
            $this->renvois->add($renvoi);
            $renvoi->setAffaire($this);
        }

        return $this;
    }

    public function removeRenvoi(Renvoi $renvoi): self
    {
        if ($this->renvois->removeElement($renvoi)) {
            // set the owning side to null (unless already changed)
            if ($renvoi->getAffaire() === $this) {
                $renvoi->setAffaire(null);
            }
        }

        return $this;
    }

    public function getDecisions(): Collection
    {
        return $this->decisions;
    }

    public function setDecisions(array $decisions): void {
        $this->decisions = new ArrayCollection($decisions);
    }

    public function addDecision(Decision $decision): self
    {
        if (!$this->decisions->contains($decision)) {
            $this->decisions->add($decision);
            $decision->setAffaire($this);
        }

        return $this;
    }

    public function removeDecision(Decision $decision): self
    {
        if ($this->decisions->removeElement($decision)) {
            if ($decision->getAffaire() === $this) {
                $decision->setAffaire(null);
            }
        }

        return $this;
    }

    public function getNumeroDossier(): ?string
    {
      return ($this->getNumeroParquet()) ? substr($this->getNumeroParquet(),-11) : null;
    }

    public function getActiveAffaireNatinfs(): Collection
    {
      $affaireNatinfs = $this->getAffaireNatinfs();
      return $affaireNatinfs->filter(function(AffaireNatinf $affaireNatinf) {
        $natinfPersonnes = $affaireNatinf->getPersonnes();
        $dr = false;
        foreach($natinfPersonnes as $natinfPersonne) {
          $lPersonne = $natinfPersonne->getPersonne();
          $dr=$dr||(!$lPersonne->isDisqualifie($affaireNatinf));
        }
        return $dr;
      });
    }
}
