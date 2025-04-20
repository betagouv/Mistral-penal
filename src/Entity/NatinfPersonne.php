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

use App\Repository\NatinfPersonneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(schema: 'webapp', name: 'natinf_personne')]
#[ORM\Entity(repositoryClass: NatinfPersonneRepository::class)]
class NatinfPersonne
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read"])]
    private ?Personne $personne = null;

    #[ORM\Column(nullable: true)]
    private ?int $rang = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["read"])]
    private ?string $statut = null;

    #[ORM\ManyToOne]
    private ?ModaliteParticipation $modaliteParticipation = null;

    #[ORM\ManyToOne(inversedBy: 'personnes')]
    #[ORM\JoinColumn(onDelete:"CASCADE", nullable: false)]
    private ?AffaireNatinf $affaireNatinf = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(["read"])]
    private bool $isDisqualifie = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRang(): ?int
    {
        return $this->rang;
    }

    public function setRang(?int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getModaliteParticipation(): ?ModaliteParticipation
    {
        return $this->modaliteParticipation;
    }

    public function setModaliteParticipation(?ModaliteParticipation $modaliteParticipation): self
    {
        $this->modaliteParticipation = $modaliteParticipation;

        return $this;
    }

    public function getAffaireNatinf(): ?AffaireNatinf
    {
        return $this->affaireNatinf;
    }

    public function setAffaireNatinf(?AffaireNatinf $affaireNatinf): self
    {
        $this->affaireNatinf = $affaireNatinf;

        return $this;
    }

    public function isDisqualifie(): ?bool
    {
        return $this->isDisqualifie;
    }

    public function setIsDisqualifie(bool $isDisqualifie): static
    {
        $this->isDisqualifie = $isDisqualifie;

        return $this;
    }
}
