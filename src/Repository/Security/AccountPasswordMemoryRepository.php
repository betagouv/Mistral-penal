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
namespace App\Repository\Security;

use App\Entity\Security\Account;
use App\Entity\Security\AccountPasswordMemory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends ServiceEntityRepository<AccountPasswordMemory>
 *
 * @method AccountPasswordMemory|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountPasswordMemory|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountPasswordMemory[]    findAll()
 * @method AccountPasswordMemory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountPasswordMemoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountPasswordMemory::class);
    }

    public function isPasswordAllreadyUsed(UserPasswordHasherInterface $passwordHasher, Account $account, string $password): bool
    {
        $cloneAccount = clone $account;
        $oldPasswords = $account->getAccountPasswordMemories();
        foreach($oldPasswords as $oldPassword) {
          $cloneAccount->setPassword($oldPassword->getPassword());
          if($passwordHasher->isPasswordValid($cloneAccount, $password))
            return true;
        }
        return false;
    }

    public function freeze(Account $account): AccountPasswordMemory
    {
        $accountPasswordMemory = new AccountPasswordMemory();
        $accountPasswordMemory->setAccount($account);
        $accountPasswordMemory->setPassword($account->getPassword());
        $accountPasswordMemory->setDate(new \DateTime());
        $em = $this->getEntityManager();
        $em->persist($accountPasswordMemory);
        return $accountPasswordMemory;
    }
}
