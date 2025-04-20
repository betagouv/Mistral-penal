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

use App\Entity\HorodatageFait;
use App\Entity\Security\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HorodatageFait>
 *
 * @method HorodatageFait|null find($id, $lockMode = null, $lockVersion = null)
 * @method HorodatageFait|null findOneBy(array $criteria, array $orderBy = null)
 * @method HorodatageFait[]    findAll()
 * @method HorodatageFait[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HorodatageFaitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HorodatageFait::class);
    }

    public function save(HorodatageFait $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function duplicate(?HorodatageFait $entity): ?HorodatageFait
    {
        if(null === $entity)
          return null;

        $horodatage = new HorodatageFait();
        $horodatage->setOperateur($entity->getOperateur());
        $horodatage->setDate($entity->getDate());
        $horodatage->setHeure($entity->getHeure());
        $this->getEntityManager()->persist($horodatage);
        return $horodatage;
    }

    public static function isSame(?HorodatageFait $a, ?HorodatageFait $b): bool
    {
        if(null === $a && null !== $b)
          return false;
        if(null !== $a && null === $b)
          return false;
        /** @var ?string $aDate */
        $aDate = $a->getDate() ? $a->getDate()->format('Y-m-d') : null;
        /** @var ?string $bDate */
        $bDate = $b->getDate() ? $b->getDate()->format('Y-m-d') : null;
        $aHour = $a->getHeure() ? (int)$a->getHeure()->format('H') : 0;
        $bHour = $b->getHeure() ? (int)$b->getHeure()->format('H') : 0;
        $aMin = $a->getHeure() ? (int)$a->getHeure()->format('i') : 0;
        $bMin = $b->getHeure() ? (int)$b->getHeure()->format('i') : 0;
        $cmp = (
          $a->getOperateur() == $b->getOperateur() &&
          $aDate == $bDate &&
          $aHour == $bHour &&
          $aMin == $bMin
        );
        return $cmp;
    }

    public function remove(HorodatageFait $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    

}
