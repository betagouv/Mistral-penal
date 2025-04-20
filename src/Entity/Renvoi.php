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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Post;
use App\Repository\RenvoiRepository;
use App\Service\Encryption\EncryptionHelperTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    operations: [
        new Get(),
        new Patch(),
        new Delete(),
        new GetCollection(),
        new Post(securityPostDenormalize: 'is_granted(\'CREATE\', object)')
    ],
    normalizationContext: [
        "skip_null_values" => false,
    ],
)]
#[ORM\Entity(repositoryClass: RenvoiRepository::class)]
#[ORM\Table(schema: 'webapp', name: 'renvoi')]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['affaire' => 'exact'])]
class Renvoi
{
    use EncryptionHelperTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(targetEntity: Affaire::class, inversedBy: 'renvois')]
    #[ORM\JoinColumn(onDelete: "CASCADE")]
    private ?Affaire $affaire = null;

    #[Groups(["read"])]
    #[ORM\ManyToOne(targetEntity: RenvoiMotif::class)]
    private ?RenvoiMotif $renvoiMotif = null;

    #[ORM\ManyToMany(targetEntity: AffairePersonne::class, inversedBy: 'renvois')]
    #[ORM\JoinTable(schema: 'webapp', name: 'renvoi_affaire_personne')]
    #[Groups(["read"])]
    private Collection $affairePersonnes;

    #[Groups(["read"])]
    #[ORM\ManyToOne(targetEntity: MesureSurete::class)]
    private ?MesureSurete $mesureSurete = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["read"])]
    private string $detailsMesureSurete = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["read"])]
    private string $expertise = '';

    public function __construct()
    {
        $this->affairePersonnes = new ArrayCollection();
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

    public function getAffaire(): ?Affaire
    {
        return $this->affaire;
    }

    public function setAffaire(?Affaire $affaire): self
    {
        $this->affaire = $affaire;

        return $this;
    }

    public function getRenvoiMotif(): ?RenvoiMotif
    {
        return $this->renvoiMotif;
    }

    public function setRenvoiMotif(?RenvoiMotif $renvoiMotif): self
    {
        $this->renvoiMotif = $renvoiMotif;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAffairePersonnes(): Collection
    {
        return $this->affairePersonnes;
    }

    public function hasAffairePersonne(AffairePersonne $affairePersonne): bool
    {
        foreach ($this->affairePersonnes as $demandeur)
            if ($demandeur->getId() === $affairePersonne->getId())
                return true;
        return false;
    }

    public function addAffairePersonne(AffairePersonne $affairePersonne): self
    {
        if (!$this->affairePersonnes->contains($affairePersonne)) {
            $this->affairePersonnes->add($affairePersonne);
        }

        return $this;
    }

    public function removeAffairePersonne(AffairePersonne $affairePersonne): self
    {
        if ($this->affairePersonnes->contains($affairePersonne)) {
            $this->affairePersonnes->removeElement($affairePersonne);
        }

        return $this;
    }

    public function getMesureSurete(): ?MesureSurete
    {
        return $this->mesureSurete;
    }

    public function setMesureSurete(?MesureSurete $mesureSurete): Renvoi
    {
        $this->mesureSurete = $mesureSurete;
        return $this;
    }

    public function getDetailsMesureSurete(): string
    {
        return $this->getDecryption('detailsMesureSurete');
    }

    public function setDetailsMesureSurete(string $detailsMesureSurete): Renvoi
    {
        $this->setEncryption('detailsMesureSurete', $detailsMesureSurete);
        return $this;
    }

    public function getExpertise(): string
    {
        return $this->getDecryption('expertise');
    }

    public function setExpertise(string $expertise): Renvoi
    {
        $this->setEncryption('expertise', $expertise);
        return $this;
    }
}
