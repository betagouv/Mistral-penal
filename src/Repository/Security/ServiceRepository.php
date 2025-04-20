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
use App\Entity\Security\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Service>
 *
 * @method Service|null find($id, $lockMode = null, $lockVersion = null)
 * @method Service|null findOneBy(array $criteria, array $orderBy = null)
 * @method Service[]    findAll()
 * @method Service[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function save(Service $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Service $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByUser(Account $user) {
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        return $em->createQuery('
            SELECT s
            FROM App\Entity\Security\Service s
            JOIN s.accountServices accService
            JOIN accService.account a
            WHERE a.id = :id
        ')
        ->setParameter(':id', $user->getId())
        ->getResult();
    }

    public function findOneByUserAndLabel(Account $user, string $label,?string $tribunal=null): ?Service
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        /** @var Connection $conn */
        $conn = $em->getConnection();

        $sql = '
        SELECT s.id
        FROM webapp.service s
        INNER JOIN webapp.account_service t ON s.id = t.service_id
        INNER JOIN webapp.account a ON a.id = t.account_id
        WHERE a.id = :id AND LOWER(s.label) LIKE LOWER(:label)
        ';
        if($tribunal)
          $sql.=" AND LOWER(s.tribunal) LIKE LOWER(:tribunal)";
        $stmt = $conn->prepare($sql);
        $userId = $user->getId();
        $stmt->bindParam(":id",$userId, \PDO::PARAM_INT);
        $stmt->bindParam(":label",$label, \PDO::PARAM_STR);
        if($tribunal)
          $stmt->bindParam(":tribunal",$tribunal, \PDO::PARAM_STR);
        $result = $stmt->execute();
        /** @var array $rec */
        $rec = !is_bool($result) ? $result->fetch() : $stmt->fetch();
        /** @var ?Service $service */
        $service = ($rec) ? $this->find($rec['id']) : null;
        return $service;
    }
}
