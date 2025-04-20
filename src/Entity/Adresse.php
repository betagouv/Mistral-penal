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
use App\Repository\AdresseRepository;
use App\Service\Encryption\EncryptionHelperTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [new Get(), new Put()], normalizationContext: ['groups' => ['read']])]
#[ORM\Table(schema: 'webapp', name: 'adresse')]
#[ORM\Entity(repositoryClass: AdresseRepository::class)]
class Adresse
{
    use EncryptionHelperTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $ligne1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $ligne2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $ligne3 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $lieuDit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $codePostal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["read"])]
    private ?string $localite = null;

    #[ORM\ManyToOne]
    #[Groups(["read"])]
    private ?Pays $pays = null;

    #[ORM\OneToOne(mappedBy: 'adresse', cascade: ['persist', 'remove'])]
    private ?Personne $personne = null;

    public function __construct(?EntityManagerInterface $em=null) {
      $this->setLigne1("");
      $this->setLigne2("");
      $this->setLigne3("");
      if(null !== $em) {
        $pays = $em
          ->getRepository(Pays::class)
          ->findOneBy(['mnemo' => 'FR'])
        ;
        $this->setPays($pays);
      }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLigne1(): ?string
    {
        return $this->getDecryption('ligne1', $this->ligne1);
    }

    public function setLigne1(?string $ligne1): self
    {
        $this->setEncryption('ligne1', $ligne1);

        return $this;
    }

    public function getLigne2(): ?string
    {
        return $this->getDecryption('ligne2', $this->ligne2);
    }

    public function setLigne2(?string $ligne2): self
    {
        $this->setEncryption('ligne2', $ligne2);

        return $this;
    }

    public function getLigne3(): ?string
    {
        return $this->getDecryption('ligne3', $this->ligne3);
    }

    public function setLigne3(?string $ligne3): self
    {
        $this->setEncryption('ligne3', $ligne3);

        return $this;
    }

    public function getLieuDit(): ?string
    {
        return $this->getDecryption('lieuDit', $this->lieuDit);
    }

    public function setLieuDit(?string $lieuDit): self
    {
        $this->setEncryption('lieuDit', $lieuDit);

        return $this;
    }

    public function getCodePostal(): ?string
    {
        $cp = $this->getDecryption('codePostal', $this->codePostal);
        if($cp)
          $cp = str_pad($cp,5,"0",STR_PAD_LEFT);
        return $cp;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->setEncryption('codePostal', $codePostal);

        return $this;
    }

    public function getLocalite(): ?string
    {
        return $this->getDecryption('localite', $this->localite);
    }

    public function setLocalite(?string $localite): self
    {
        $this->setEncryption('localite', $localite);

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getPersonne(): ?Personne
    {
        return $this->personne;
    }

    public function getAdresseComplete(): string
    {
      $adresse = [];
      if($this->getLigne1())
        $adresse[]=$this->getLigne1();
      if($this->getLigne2())
        $adresse[]=$this->getLigne2();
      if($this->getLigne3())
        $adresse[]=$this->getLigne3();
      if($this->getCodePostal())
        $adresse[]=$this->getCodePostal();
      if($this->getLocalite())
        $adresse[]=mb_strtoupper($this->getLocalite());
      if($this->getPays())
        $adresse[]="(".mb_strtoupper($this->getPays()->getLibelle()).")";
      return implode(" ", $adresse);
    }

    public function setPersonne(?Personne $personne): self
    {
        // unset the owning side of the relation if necessary
        if ($personne === null && $this->personne !== null) {
            $this->personne->setAdresse(null);
        }

        // set the owning side of the relation if necessary
        if ($personne !== null && $personne->getAdresse() !== $this) {
            $personne->setAdresse($this);
        }

        $this->personne = $personne;

        return $this;
    }
}
