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
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\RepresentantLegalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\RepresentantLegal\AddRepresentant;

#[ApiResource(operations: [new Get(), new Put(), new Delete()], normalizationContext: ['groups' => ['read']])]
#[ORM\Entity(repositoryClass: RepresentantLegalRepository::class)]
#[ORM\Table(schema: 'webapp', name: 'representant_legal')]
class RepresentantLegal
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["read"])]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'representes')]
    #[ORM\JoinColumn(onDelete: "CASCADE", nullable: false)]
    private ?AffairePersonne $representant = null;

    #[ORM\ManyToOne(inversedBy: 'representants')]
    #[ORM\JoinColumn(onDelete: "CASCADE", nullable: false)]
    #[Groups(["read"])]
    private ?AffairePersonne $represente = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?LienJuridique $lienJuridique = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?LienSocial $lienSocial = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getRepresentant(): ?AffairePersonne
    {
        return $this->representant;
    }

    public function setRepresentant(?AffairePersonne $representant): static
    {
        $this->representant = $representant;

        return $this;
    }

    public function getRepresente(): ?AffairePersonne
    {
        return $this->represente;
    }

    public function setRepresente(?AffairePersonne $represente): static
    {
        $this->represente = $represente;

        return $this;
    }

    public function getLienJuridique(): ?LienJuridique
    {
        return $this->lienJuridique;
    }

    public function setLienJuridique(?LienJuridique $lienJuridique): static
    {
        $this->lienJuridique = $lienJuridique;

        return $this;
    }

    public function getLienSocial(): ?LienSocial
    {
        return $this->lienSocial;
    }

    public function setLienSocial(?LienSocial $lienSocial): static
    {
        $this->lienSocial = $lienSocial;

        return $this;
    }
}
