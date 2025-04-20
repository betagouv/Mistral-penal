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

use App\Entity\Security\Account;
use App\Entity\Affaire;
use App\Entity\NoteAudience;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NoteAudience>
 *
 * @method NoteAudience|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoteAudience|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoteAudience[]    findAll()
 * @method NoteAudience[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoteAudienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoteAudience::class);
    }

    public function insertOrUpdate(Affaire $affaire, Account $account, string $note): NoteAudience
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        
        try {
            $noteAudience = $this->findOneBy(['affaire' => $affaire], ['version' => 'DESC']);

            if ((null === $noteAudience) || ($account != $noteAudience->getAuteur())) {
                $noteAudience = new NoteAudience();
                $noteAudience->setAffaire($affaire);
                $noteAudience->setAuteur($account);
                $noteAudience->setDate(new \DateTime());
            }
            $noteAudience->setNote($note);
            $noteAudience->setUpdate(new \DateTime());

            $em->persist($noteAudience);
            $em->flush();
            $em->getConnection()->commit();

        }  catch (\Exception $e) {
            $em->getConnection()->rollback();

            throw $e;
        }


        return $noteAudience;
    }
}
