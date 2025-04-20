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
namespace App\Repository;

use App\Entity\CassiopeeImportJob;
use App\Entity\Security\Account;
use App\Security\AuthorizationFilterInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CassiopeeImportJob>
 *
 * @method CassiopeeImportJob|null find($id, $lockMode = null, $lockVersion = null)
 * @method CassiopeeImportJob|null findOneBy(array $criteria, array $orderBy = null)
 * @method CassiopeeImportJob[]    findAll()
 * @method CassiopeeImportJob[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CassiopeeImportJobRepository extends ServiceEntityRepository implements AuthorizationFilterInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CassiopeeImportJob::class);
    }


    public function authorizationFilter(QueryBuilder $queryBuilder, Account $user) {
    }
    public function canCreate($renvoi, Account $user): bool {
        return false;
    }
}
