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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\IntervenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [])]
#[ORM\Table(schema: 'webapp', name: 'intervenant')]
#[ORM\Entity(repositoryClass: IntervenantRepository::class)]
#[UniqueEntity('idKsp')]
class Intervenant
{
    const ROLES = [ 'president', 'assesseur1', 'assesseur2', 'auditeur', 'ministere', 'greffe', 'greffe_stagiaire' ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(["read"])]
    private ?string $role = null;

    #[ORM\Column(length: 255)]
    #[Groups(["read"])]
    private ?string $nomComplet = null;

    #[ORM\Column(length: 50)]
    private ?string $idKsp = null;

    #[ORM\ManyToMany(targetEntity: Audience::class, inversedBy: 'intervenants')]
    #[ORM\JoinTable(schema: 'webapp', name: 'audience_intervenant')]
    private Collection $audiences;

    #[ORM\ManyToMany(targetEntity: Affaire::class, inversedBy: 'intervenants')]
    #[ORM\JoinTable(schema: 'webapp', name: 'affaire_intervenant')]
    private Collection $affaires;

    public function __construct()
    {
        $this->audiences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        if(!in_array($role, self::ROLES))
          throw new \Exception("unknown role '$role' ! only ".implode(",", self::ROLES). " accepted");

        $this->role = $role;

        return $this;
    }

    public function getNomComplet(): ?string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): self
    {
        $this->nomComplet = $nomComplet;

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

    /**
     * @return Collection<int, Audience>
     */
    public function getAudiences(): Collection
    {
        return $this->audiences;
    }

    public function addAudience(Audience $audience): self
    {
        if (!$this->audiences->contains($audience)) {
            $this->audiences->add($audience);
        }

        return $this;
    }

    public function removeAudience(Audience $audience): self
    {
        $this->audiences->removeElement($audience);

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
        }

        return $this;
    }

    public function removeAffaire(Affaire $affaire): self
    {
        $this->affaires->removeElement($affaire);

        return $this;
    }
}
