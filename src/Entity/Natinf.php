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

use App\Contracts\ReferentielEntityInterface;
use App\Repository\NatinfRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(schema: 'webapp', name: 'natinf')]
#[ORM\Entity(repositoryClass: NatinfRepository::class)]
class Natinf implements ReferentielEntityInterface
{
    use ReferentielTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'natinf', targetEntity: NatinfVersion::class)]
    #[Groups(["read"])]
    private Collection $versions;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Nataff $nataff = null;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, NatinfVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(NatinfVersion $version): static
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
            $version->setNatinf($this);
        }

        return $this;
    }

    public function removeVersion(NatinfVersion $version): static
    {
        if ($this->versions->removeElement($version)) {
            // set the owning side to null (unless already changed)
            if ($version->getNatinf() === $this) {
                $version->setNatinf(null);
            }
        }

        return $this;
    }

    public function getNataff(): ?Nataff
    {
        return $this->nataff;
    }

    public function setNataff(?Nataff $nataff): static
    {
        $this->nataff = $nataff;

        return $this;
    }

    public function getCodeLibelle():string
    {
        return $this->getCode().' - '.$this->getLibelle();
    }

    public function getPlaintext(): string
    {
        return $this->getCodeLibelle();
    }
}
