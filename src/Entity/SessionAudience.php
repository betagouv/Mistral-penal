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
use App\Entity\Security\Account;
use App\Repository\SessionAudienceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [], normalizationContext: ['groups' => ['read']])]
#[ORM\Entity(repositoryClass: SessionAudienceRepository::class)]
#[ORM\Table(schema: 'webapp', name: 'session_audience')]
class SessionAudience
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $debutSession = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable: true)]
    #[Groups(["read"])]
    private ?\DateTimeInterface $finSession = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(onDelete:"CASCADE", nullable: false)]
    private ?Audience $audience = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    public function __construct()
    {
        $this->debutSession = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDebutSession(): ?\DateTimeInterface
    {
        return $this->debutSession;
    }

    public function setDebutSession(\DateTimeInterface $debutSession): self
    {
        $this->debutSession = $debutSession;

        return $this;
    }
    
    public function getFinSession(): ?\DateTimeInterface
    {
        return $this->finSession;
    }

    public function setFinSession(\DateTimeInterface $finSession): self
    {
        $this->finSession = $finSession;

        return $this;
    }

    public function getAudience(): ?Audience
    {
        return $this->audience;
    }

    public function setAudience(?Audience $audience): self
    {
        $this->audience = $audience;

        return $this;
    }
    
    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }
}
