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
use App\Entity\MotRapide;
use App\Repository\Security\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(schema: 'webapp', name: 'account')]
#[ORM\Index(columns: ['username_hash'], name: 'username_hash_idx')]
#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[UniqueEntity('username')]
class Account implements UserInterface, PasswordAuthenticatedUserInterface
{
    use EncryptionHelperTrait;

    const CIVILITY_MAN    = 'm';
    const CIVILITY_WOMAN  = 'mme';

    const ROLE_ADMIN_FONC = 'ROLE_ADMIN_FONC';
    const ROLE_ADMIN_TECH = 'ROLE_ADMIN_TECH';
    const ROLE_DIRECTEUR_GREFFE = 'ROLE_DIRECTEUR_GREFFE';
    const ROLE_GREFFE = 'ROLE_GREFFE';
    const ROLE_USER = 'ROLE_USER';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $username = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $username_hash = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $roles;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $secondFirstname = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $mnemo = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $function = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $civility = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idKsp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $localKey = null;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: AccountService::class)]
    private Collection $accountServices;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: MotRapide::class)]
    private Collection $motRapides;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateChangementMDP = null;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: AccountPasswordMemory::class)]
    private Collection $accountPasswordMemories;

    public function __construct()
    {
        $this->accountServices = new ArrayCollection();
        $this->motRapides = new ArrayCollection();
        $this->accountPasswordMemories = new ArrayCollection();
        $this->setRoles([]);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isAdminFonc(): bool
    {
        $roles = $this->getRoles();
        return in_array(self::ROLE_ADMIN_FONC, $roles);
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->getDecryption('username');
    }

    public function setUsername(string $username): self
    {
        $this->username_hash = $this->pepperedHash($username);
        $this->setEncryption('username', $username);

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->getDecryption('roles');
        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLE_USER;

        return array_unique($roles);
    }

    public function addRole(string $role): self
    {
        $roles = $this->getRoles();
        if(
          !in_array($role, $roles)
          &&
          in_array($role, [
            self::ROLE_USER,
            self::ROLE_GREFFE,
            self::ROLE_ADMIN_TECH,
            self::ROLE_DIRECTEUR_GREFFE,
            self::ROLE_ADMIN_FONC,
          ])
        ) {
          $roles[]=$role;
          $this->setRoles($roles);
        }

        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->setEncryption('roles', $roles);

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->getDecryption('firstname');
    }

    public function setFirstname(?string $firstname): self
    {
        $this->setEncryption('firstname', $firstname);

        return $this;
    }

    public function getSecondFirstname(): ?string
    {
        return $this->getDecryption('secondFirstname');
    }

    public function setSecondFirstname(?string $secondFirstname): self
    {
        $this->setEncryption('secondFirstname', $secondFirstname);

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->getDecryption('lastname');
    }

    public function setLastname(?string $lastname): self
    {
        $this->setEncryption('lastname', $lastname);

        return $this;
    }

    public function getMnemo(): ?string
    {
        return $this->getDecryption('mnemo');
    }

    public function setMnemo(string $mnemo): self
    {
        $this->setEncryption('mnemo', $mnemo);

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->getDecryption('email');
    }

    public function setEmail(string $email): self
    {
        $this->setEncryption('email', $email);

        return $this;
    }

    public function getFunction(): ?string
    {
        return $this->getDecryption('function');
    }

    public function setFunction(?string $function): self
    {
        $this->setEncryption('function', $function);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->getDecryption('title');
    }

    public function setTitle(?string $title): self
    {
        $this->setEncryption('title', $title);

        return $this;
    }

    public function getCivility(): ?string
    {
        return $this->civility;
    }

    public function setCivility(?string $civility): self
    {
        $this->civility = $civility;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): self
    {
        $this->grade = $grade;

        return $this;
    }

    public function getIdKsp(): ?string
    {
        return $this->idKsp;
    }

    public function setIdKsp(?string $idKsp): self
    {
        $this->idKsp = $idKsp;

        return $this;
    }

    public function getLocalKey(): ?string
    {
        return $this->getDecryption('localKey');
    }

    public function setLocalKey(?string $key): self
    {
        $this->setEncryption('localKey', $key);

        return $this;
    }

    public function getServices(): array {
        $services = [];
        foreach ($this->accountServices as $as) {
            $services[] = $as->getService();
        }

        return $services;
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
            $accountService->setAccount($this);
        }

        return $this;
    }

    public function removeAccountService(AccountService $accountService): self
    {
        if ($this->accountServices->removeElement($accountService)) {
            // set the owning side to null (unless already changed)
            if ($accountService->getAccount() === $this) {
                $accountService->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MotRapide>
     */
    public function getMotRapides(): Collection
    {
        return $this->motRapides;
    }

    public function addMotRapide(MotRapide $motRapide): self
    {
        if (!$this->motRapides->contains($motRapide)) {
            $this->motRapides->add($motRapide);
            $motRapide->setAccount($this);
        }

        return $this;
    }

    public function removeMotRapide(MotRapide $motRapide): self
    {
        if ($this->motRapides->removeElement($motRapide)) {
            // set the owning side to null (unless already changed)
            if ($motRapide->getAccount() === $this) {
                $motRapide->setAccount(null);
            }
        }

        return $this;
    }

    public function getRolesPlaintext(): string {
      return implode(", ", $this->getRoles());
    }

    public function getDateChangementMDP(): ?\DateTimeInterface
    {
        return $this->dateChangementMDP;
    }

    public function setDateChangementMDP(?\DateTimeInterface $dateChangementMDP): static
    {
        $this->dateChangementMDP = $dateChangementMDP;

        return $this;
    }

    /**
     * @return Collection<int, AccountPasswordMemory>
     */
    public function getAccountPasswordMemories(): Collection
    {
        return $this->accountPasswordMemories;
    }

    public function addAccountPasswordMemory(AccountPasswordMemory $accountPasswordMemory): static
    {
        if (!$this->accountPasswordMemories->contains($accountPasswordMemory)) {
            $this->accountPasswordMemories->add($accountPasswordMemory);
            $accountPasswordMemory->setAccount($this);
        }

        return $this;
    }

    public function removeAccountPasswordMemory(AccountPasswordMemory $accountPasswordMemory): static
    {
        if ($this->accountPasswordMemories->removeElement($accountPasswordMemory)) {
            // set the owning side to null (unless already changed)
            if ($accountPasswordMemory->getAccount() === $this) {
                $accountPasswordMemory->setAccount(null);
            }
        }

        return $this;
    }
}
