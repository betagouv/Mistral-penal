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

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

final class AuthorizationExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface {

    public function __construct(private Security $security, private EntityManagerInterface $em, private LoggerInterface $logger) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder, 
        QueryNameGeneratorInterface $queryNameGeneratorInterface,
        string $resourceClass,
        Operation $operation = null,
        array $context = []): void {

        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder, 
        QueryNameGeneratorInterface $queryNameGeneratorInterface,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []): void {

        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere($queryBuilder, $resourceClass): void {
        $repository = $this->em->getRepository($resourceClass);

        if (!$repository instanceof AuthorizationFilterInterface) {
            throw new RuntimeException(
                "The repository class for entity '" . $resourceClass . "' must implement the 'AuthorizationFilterRepository' interface ! "
            );
        }

        $repository->authorizationFilter($queryBuilder, $this->security->getUser());
    }

}