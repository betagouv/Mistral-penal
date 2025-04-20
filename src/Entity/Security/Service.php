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

use App\Entity\Audience;
use App\Repository\Security\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Table(schema: 'webapp', name: 'service')]
#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[UniqueEntity('label')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length:255, nullable: true)]
    #[Groups(["read"])]
    private ?string $idKsp = null;

    #[ORM\Column(length: 255)]
    #[Groups(["read"])]
    private ?string $label = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["read"])]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: AccountService::class)]
    private Collection $accountServices;

    #[ORM\Column(length: 40)]
    #[Groups(["read"])]
    private ?string $mnemo = null;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Audience::class)]
    private Collection $audiences;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tribunal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $juridiction = null;

    #[ORM\Column(nullable: true)]
    private ?int $code = null;

    #[ORM\Column(nullable: true)]
    private ?int $numero = null;

    public function __construct()
    {
        $this->accountServices = new ArrayCollection();
        $this->audiences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setIdKsp(string $idKsp): void {
        $this->idKsp = $idKsp;
    }

    public function getIdKsp(): ?string {
        return $this->idKsp;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        $this->slug = $this->generateSlug($label);

        return $this;
    }

    public static function generateSlug(string $label): string
    {
        $slugger = new AsciiSlugger();
        return mb_strtolower($slugger->slug($label));
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @return Collection<int, AccountService>
     */
    public function getAccountServices(): Collection
    {
        return $this->accountServices;
    }

    public function addAccountService(AccountService $accountService): self
    {
        if (!$this->accountServices->contains($accountService)) {
            $this->accountServices->add($accountService);
            $accountService->setService($this);
        }

        return $this;
    }

    public function removeAccountService(AccountService $accountService): self
    {
        if ($this->accountServices->removeElement($accountService)) {
            // set the owning side to null (unless already changed)
            if ($accountService->getService() === $this) {
                $accountService->setService(null);
            }
        }

        return $this;
    }

    public function getMnemo(): ?string
    {
        return $this->mnemo;
    }

    public function setMnemo(string $mnemo): self
    {
        $this->mnemo = $mnemo;

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
            $audience->setService($this);
        }

        return $this;
    }

    public function removeAudience(Audience $audience): self
    {
        if ($this->audiences->removeElement($audience)) {
            // set the owning side to null (unless already changed)
            if ($audience->getService() === $this) {
                $audience->setService(null);
            }
        }

        return $this;
    }

    public function getTribunal(): ?string
    {
        return $this->tribunal;
    }

    public function setTribunal(?string $tribunal): static
    {
        $this->tribunal = $tribunal;

        return $this;
    }

    public function getJuridiction(): ?string
    {
        return $this->juridiction;
    }

    public function setJuridiction(?string $juridiction): static
    {
        $this->juridiction = $juridiction;

        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }
}
