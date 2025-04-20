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

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\Audience\GetImport;
use App\Controller\Audience\Refresh;
use App\Controller\Audience\UpdateAffairePositions;
use App\Entity\Security\Service;
use App\Repository\AudienceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ApiResource(
    operations: [
        new GetCollection(
            read: false,
            controller: GetImport::class,
            uriTemplate: '/audiences/v1/importation'
        ),
        new GetCollection(
            controller: UpdateAffairePositions::class,
            uriTemplate: '/audiences/{id}/positions_mise_a_jour'
        )
    ],
    normalizationContext: ['groups' => ['read']]
)]
#[ORM\Table(schema: 'webapp', name: 'audience')]
#[ORM\Entity(repositoryClass: AudienceRepository::class)]
#[UniqueEntity('idKsp')]
#[ORM\Index(name: "audience_date_idx", columns: ["date"])]
class Audience
{
    const TYPE_JUGE_UNIQUE='juge unique';
    const TYPE_COLLEGIAL='collegial';
    const ROLE_PRESIDENT='president';
    const ROLE_MINISTERE='ministere';
    const ROLE_ASSESSEUR_1='assesseur1';
    const ROLE_ASSESSEUR_2='assesseur2';
    const ROLE_GREFFE='greffe';
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'audiences')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read"])]
    private ?Service $service = null;

    #[ORM\Column(length: 5)]
    #[Groups(["read"])]
    private ?string $debut = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(["read"])]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(["read"])]
    private ?string $idKsp = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(["read"])]
    private ?string $evaluatedTime = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(["read"])]
    private ?string $estimatedTime = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    private ?int $numberOfFolders = null;

    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $maxNumberOfFolders = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read"])]
    public ?string $plaintext = null;

    #[Groups(["read"])]
    #[ORM\ManyToMany(targetEntity: Intervenant::class, mappedBy: 'audiences')]
    private Collection $intervenants;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["read"])]
    private ?string $specialty = null;

    #[ORM\OneToMany(mappedBy: 'audience', targetEntity: Affaire::class)]
    #[ORM\OrderBy(["position" => "asc"])]
    #[Groups(["read"])]
    private Collection $affaires;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'date_mise_a_jour', nullable: true)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $dateMiseAJour = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $dateDernierImport = null;

    #[Groups(["read"])]
    public $affairesJugees=[];

    #[ORM\OneToMany(mappedBy: 'audience', targetEntity: SessionAudience::class)]
    #[Groups(["read"])]
    private Collection $sessions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $finAudience = null;

    public function __construct()
    {
        $this->intervenants = new ArrayCollection();
        $this->affaires = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    public function getAffairesJugees(): array
    {
        $tab=[];
        foreach($this->getAffaires() as $affaire)
        {
          if($affaire->getDecisions()->count() > 0)
            $tab[]=$affaire;
        }
        return $tab;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getDebut(): ?string
    {
        return $this->debut;
    }

    public function setDebut(string $debut): self
    {
        $this->debut = $debut;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        if(in_array($type, [self::TYPE_COLLEGIAL, self::TYPE_JUGE_UNIQUE]))
          $this->type = $type;

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

    public function getEvaluatedTime(): ?string
    {
        return $this->evaluatedTime;
    }

    public function setEvaluatedTime(?string $evaluatedTime): self
    {
        $this->evaluatedTime = $evaluatedTime;

        return $this;
    }

    public function getEstimatedTime(): ?string
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(?string $estimatedTime): self
    {
        $this->estimatedTime = $estimatedTime;

        return $this;
    }

    public function getNumberOfFolders(): ?int
    {
        return $this->numberOfFolders;
    }

    public function setNumberOfFolders(?int $numberOfFolders): self
    {
        $this->numberOfFolders = $numberOfFolders;

        return $this;
    }

    public function getMaxNumberOfFolders(): ?int
    {
        return $this->maxNumberOfFolders;
    }

    public function setMaxNumberOfFolders(int $maxNumberOfFolders): self
    {
        $this->maxNumberOfFolders = $maxNumberOfFolders;

        return $this;
    }

    public function getPlaintext(): ?string
    {
      return $this->plaintext;
    }

    public function setPlaintext(string $plaintext): self
    {
      $this->plaintext = $plaintext;

      return $this;
    }

    public function generatePlaintext(?TranslatorInterface $trans=null): self
    {
      if(null === $trans)
        return $this;

      /** @var array<int, string> $block */
      $block = [];
      if(!empty($this->getDebut()))
        $block[]=$this->getDebut();
      $block[] = $this->getService()->getLabel();
      $block[] = $trans->trans("instance_of_service.type.".str_replace([" "], ["-"], $this->getType()));

      if(!$this->getEvaluatedTime())
        $block[] = str_replace([":"], ["h"], $this->getEstimatedTime());
      else
        $block[] = str_replace([":"], ["h"], $this->getEstimatedTime()).'/'.str_replace([":"], ["h"], $this->getEvaluatedTime());

      if($this->getMaxNumberOfFolders())
        $block[] = $this->getNumberOfFolders().'/'.$this->getMaxNumberOfFolders();

      $this->plaintext = implode(" - ", $block);
      return $this;
    }

    public function getQuantitePrevue(): int
    {
      return $this->getAffaires()->count();
    }

    public function getDureePrevue(): string
    {
      if(!$this->getEvaluatedTime())
        return str_replace([":"], ["h"], $this->getEstimatedTime());
      else
        return str_replace([":"], ["h"], $this->getEstimatedTime()).'/'.str_replace([":"], ["h"], $this->getEvaluatedTime());
    }
    /**
     * @return Collection<int, Intervenant>
     */
    public function getIntervenants(): Collection
    {
        return $this->intervenants;
    }

    public function getIntervenantsFromAffaire(?string $nomComplet, string $func): string
    {
      $tab = [];
      foreach($this->getAffaires() as $affaire) {
        $val = $affaire->$func();
        if(!empty($val) && !in_array($val, $tab))
          $tab[]=$val;
      }
      $nomComplets = !empty($nomComplet) ? array_merge([$nomComplet], $tab) : $tab;
      return implode(", ",array_unique($nomComplets));
    }


    public function getIntervenantByRole(string $role): ?string
    {
        foreach($this->getIntervenants() as $intervenant)
          if($intervenant->getRole() == $role)
            return $intervenant->getNomComplet();
        return null;
    }
    public function getPresident(): ?string
    {
        return $this->getIntervenantByRole(self::ROLE_PRESIDENT);
    }

    public function getMinisterePublic(): ?string
    {
        return $this->getIntervenantByRole(self::ROLE_MINISTERE);
    }

    public function getGreffier(): ?string
    {
        return $this->getIntervenantByRole(self::ROLE_GREFFE);
    }

    public function getAssesseur1(): ?string
    {
        return $this->getIntervenantByRole(self::ROLE_ASSESSEUR_1);
    }

    public function getAssesseur2(): ?string
    {
        return $this->getIntervenantByRole(self::ROLE_ASSESSEUR_2);
    }

    public function addIntervenant(Intervenant $intervenant): self
    {
        if (!$this->intervenants->contains($intervenant)) {
            $this->intervenants->add($intervenant);
            $intervenant->addAudience($this);
        }

        return $this;
    }

    public function removeIntervenant(Intervenant $intervenant): self
    {
        if ($this->intervenants->removeElement($intervenant)) {
            $intervenant->removeAudience($this);
        }

        return $this;
    }

    public function getSpecialty(): ?string
    {
        return $this->specialty;
    }

    public function setSpecialty(?string $specialty): self
    {
        $this->specialty = $specialty;

        return $this;
    }

    /**
     * @return Collection<int, Affaire>
     */
    public function getAffaires(): Collection
    {
        return $this->affaires;
    }

    public function addAffaire(Affaire $affaire): self
    {
        if (!$this->affaires->contains($affaire)) {
            $this->affaires->add($affaire);
            $affaire->setAudience($this);
        }

        return $this;
    }

    public function removeAffaire(Affaire $affaire): self
    {
        if ($this->affaires->removeElement($affaire)) {
            // set the owning side to null (unless already changed)
            if ($affaire->getAudience() === $this) {
                $affaire->setAudience(null);
            }
        }

        return $this;
    }

    public function getDateMiseAJour(): ?\DateTimeInterface
    {
        return $this->dateMiseAJour;
    }

    public function setDateMiseAJour(?\DateTimeInterface $dateMiseAJour): self
    {
        $this->dateMiseAJour = $dateMiseAJour;

        return $this;
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
     * @return Collection<int, SessionAudience>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(SessionAudience $session): self
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setAudience($this);
        }

        return $this;
    }

    public function removeSession(SessionAudience $session): self
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getAudience() === $this) {
                $session->setAudience(null);
            }
        }

        return $this;
    }

    public function getFinAudience(): ?\DateTimeInterface
    {
        return $this->finAudience;
    }

    public function setFinAudience(?\DateTimeInterface $finAudience): self
    {
        $this->finAudience = $finAudience;

        return $this;
    }

    public function getDureeAudience(){
      //@todo caclul duree audience
      foreach($this->sessions as $session){
      }
    }


    #[Groups(["read"])]
    #[SerializedName('debutAudience')]
    public function getDebutAudience(){
      if($this->sessions->count() == 0)
        return null;

      $times = [];
      foreach($this->sessions as $session){
        $times[] = $session->getDebutSession();
      }

      return min($times);
    }
}
