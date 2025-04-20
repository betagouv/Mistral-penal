<?php 

namespace App\Security;
use App\Entity\Security\Account;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T
 */
interface AuthorizationFilterInterface {
    public function authorizationFilter(QueryBuilder $queryBuilder, Account $user);

    /**
     * 
     * @param T $object
     * @param \App\Entity\Security\Account $user
     * @return bool 
     */
    public function canCreate($object, Account $user): bool;
}