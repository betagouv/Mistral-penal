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

use App\Service\Encryption\EncryptionHelperTrait;
use App\Repository\Security\UtilisateurAccrediteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurAccrediteRepository::class)]
#[ORM\Index(columns: ["username_hash"], name: 'username_hash_idx')]
class UtilisateurAccredite
{
    use EncryptionHelperTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $username = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $username_hash = null;

    #[ORM\ManyToOne(inversedBy: 'utilisateursAccredites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DonneesGenerales $donneesGenerales = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->getDecryption('username');
    }

    public function setUsername(string $username): static
    {
        $this->username_hash = $this->pepperedHash($username);
        $this->setEncryption('username', $username);

        return $this;
    }

    public function getDonneesGenerales(): ?DonneesGenerales
    {
        return $this->donneesGenerales;
    }

    public function setDonneesGenerales(?DonneesGenerales $donneesGenerales): static
    {
        $this->donneesGenerales = $donneesGenerales;

        return $this;
    }
}
