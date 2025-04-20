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
namespace App\Entity\Security;

use App\Repository\Security\DonneesGeneralesRepository;
use App\Service\Encryption\EncryptionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonneesGeneralesRepository::class)]
class DonneesGenerales
{
    use EncryptionHelperTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'donneesGenerales', targetEntity: UtilisateurAccredite::class)]
    private Collection $utilisateursAccredites;

    public function __construct()
    {
        $this->utilisateursAccredites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, UtilisateurAccredite>
     */
    public function getUtilisateursAccredites(): Collection
    {
        return $this->utilisateursAccredites;
    }

    public function addUtilisateursAccredite(UtilisateurAccredite $utilisateursAccredite): static
    {
        if (!$this->utilisateursAccredites->contains($utilisateursAccredite)) {
            $this->utilisateursAccredites->add($utilisateursAccredite);
            $utilisateursAccredite->setDonneesGenerales($this);
        }

        return $this;
    }

    public function removeUtilisateursAccredite(UtilisateurAccredite $utilisateursAccredite): static
    {
        if ($this->utilisateursAccredites->removeElement($utilisateursAccredite)) {
            // set the owning side to null (unless already changed)
            if ($utilisateursAccredite->getDonneesGenerales() === $this) {
                $utilisateursAccredite->setDonneesGenerales(null);
            }
        }

        return $this;
    }
}
