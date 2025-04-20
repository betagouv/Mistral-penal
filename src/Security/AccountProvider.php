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
namespace App\Security;

use App\Entity\Security\Account;
use App\Service\CryptologyHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AccountProvider implements UserProviderInterface
{
    public function __construct(private EntityManagerInterface $em, private LoggerInterface $logger) {}

    public function supportsClass(string $class): bool {
        return Account::class == $class || is_subclass_of($class, Account::class);
    }

    public function loadUserByUsername(string $identifier): UserInterface {
        return $this->loadUser(null, $identifier);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface {
        return $this->loadUser(null, $identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface {
        return $this->loadUser($user, null);
    }

    private function loadUser(?UserInterface $user, ?string $identifier): UserInterface {

        $qb = $this->em->createQueryBuilder()
            ->select('a,ac,s')
            ->from(Account::class, 'a')
            ->leftJoin('a.accountServices', 'ac')
            ->leftJoin('ac.service', 's');

        if ($user !== null && $user instanceof Account) {
            $qb = $qb->where('a.id = :id')->setParameter('id', $user->getId());
        } else if ($identifier !== null) {
            $qb = $qb
                ->where('a.username_hash = :username_hash')
                ->setParameter(
                    'username_hash',
                    CryptologyHelper::pepperedHash($identifier)
                );
        } else {
            throw new UserNotFoundException('missing id or identifier !');
        }

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            throw new UserNotFoundException('', 0, $e);
        }
    }
}
