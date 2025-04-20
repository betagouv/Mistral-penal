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

use App\Entity\Personne;
use App\Entity\AffaireNatinf;
use App\Entity\HorodatageFait;
use App\Entity\Security\Account;
use App\Security\AuthorizationFilterInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffaireNatinf>
 *
 * @method AffaireNatinf|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffaireNatinf|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffaireNatinf[]    findAll()
 * @method AffaireNatinf[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffaireNatinfRepository extends ServiceEntityRepository implements AuthorizationFilterInterface
{
    const NATURE_REQUALIFICATION = 'requalification';
    const NATURE_MODIFICATION = 'modification';
    const NATURE_ANNULATION = 'annulation';
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffaireNatinf::class);
    }

    public function getAffaireNatinf(int $affaireNatinfId, Account $user): AffaireNatinf {
        $query = $this->createQueryBuilder("an")
            ->andWhere("an.id = :affaireNatinfId")
            ->setParameter("affaireNatinfId", $affaireNatinfId);

        $this->authorizationFilter($query, $user);

        return $query->getQuery()->getSingleResult();
    }

    public function duplicate(AffaireNatinf $entity): AffaireNatinf
    {
      /** @var AffaireNatinf $root */
      $root = $entity->getDuplicateRoot() ?? $entity;

      $affaireNatinf = new AffaireNatinf();
      $affaireNatinf->setAffaire($entity->getAffaire());
      $affaireNatinf->setNatinf($entity->getNatinf());
      $affaireNatinf->setLieu($entity->getLieu());
      $affaireNatinf->setCommune($entity->getCommune());
      $affaireNatinf->setQualificationDeveloppee($entity->getQualificationDeveloppee());
      $affaireNatinf->setDuplicateParent($entity);
      $affaireNatinf->setDuplicateRoot($root);
      $debut = $this
        ->getEntityManager()
        ->getRepository(HorodatageFait::class)
        ->duplicate($entity->getDebut())
      ;
      $affaireNatinf->setDebut($debut);

      $fin = $this
        ->getEntityManager()
        ->getRepository(HorodatageFait::class)
        ->duplicate($entity->getFin())
      ;
      $affaireNatinf->setFin($fin);

      $this->getEntityManager()->persist($affaireNatinf);

      return $affaireNatinf;
    }

    public function save(AffaireNatinf $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AffaireNatinf $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Objectif :
     * 1. détacher la personne de l'affaireNatinf
     * 2. SI l'affaire_natinf a 0 personnes rattachée ALORS on supprime l'affaire_natinf
     * 3. on enlève le flag is_disqualifie sur l'affaire_natinf de clé duplicate_root
     */
    public function revertDisqual(AffaireNatinf $affaireNatinf, Personne $personne): ?AffaireNatinf
    {
      /** @var ?AffaireNatinf $root */
      $root = $affaireNatinf->getDuplicateRoot();
      if(null === $root)
        return null;

      /** @var EntityManagerInterface $em */
      $em = $this->getEntityManager();
      /** @var Collection<int, AffaireNatinf> $requalAffaireNatinfs */
      $requalAffaireNatinfs = $this->findBy([
        'duplicateRoot' => $root
      ],['id' => 'desc']);
      foreach($requalAffaireNatinfs as $requalAffaireNatinf)
      {
        $deleteIds = [];
        $this->detachPersonne($requalAffaireNatinf, $personne);
        if(0 === $requalAffaireNatinf->getPersonnes()->count()) {
          $requalAffaireNatinf->setDuplicateRoot(null);
          $requalAffaireNatinf->setDuplicateParent(null);
          $em->remove($requalAffaireNatinf);
          $em->flush();
        }
      }

      /** @var NatinfPersonne $natinfPersonne */
      foreach($root->getPersonnes() as $natinfPersonne)
        if($natinfPersonne->getPersonne() === $personne)
          $natinfPersonne->setIsDisqualifie(false);
      $em->flush();

      return $root;
    }

    public function detachPersonne(AffaireNatinf $affaireNatinf, Personne $personne): bool
    {
      $em = $this->getEntityManager();
      foreach($affaireNatinf->getPersonnes() as $natinfPersonne) {
        if($natinfPersonne->getPersonne() == $personne) {
          $affaireNatinf->removePersonne($natinfPersonne);
          $em->remove($natinfPersonne);
          $em->flush();
          return true;
        }
      }
      return false;
    }

    private static function check(?string $new, ?string $old): array
    {
        return [
          'new' => $new,
          'old' => $old,
          'change' => ($new != $old),
        ];
    }

    public static function diff(AffaireNatinf $a, ?AffaireNatinf $b): array
    {
      if(null === $b)
        $b = $a;
      $data = [
        'id' => ['new' => $a->getId(), 'old' => $b->getId()],
        'natinfPersonnes' => [
          'new' => $a->getNatinfPersonneIds(),
          'old' => $b->getNatinfPersonneIds(),
        ],
        'personnes' => [
          'new' => $a->getPersonneIds(),
          'old' => $b->getPersonneIds(),
        ],
        'date' => self::check($a->getDatePlaintext(),$b->getDatePlaintext()),
        'natinf' => self::check($a->getNatinfPlaintext(),$b->getNatinfPlaintext()),
        'qd' => self::check($a->getQualificationDeveloppee(),$b->getQualificationDeveloppee()),
        'commune' => self::check($a->getCommunePlaintext(),$b->getCommunePlaintext()),
        'lieu' => self::check($a->getLieu(),$b->getLieu()),
        'nature' => self::NATURE_MODIFICATION,
      ];

      if(true === $data['date']['change'] || true === $data['natinf']['change'])
        $data['nature']=self::NATURE_REQUALIFICATION;
      return $data;
    }

    public function authorizationFilter(QueryBuilder $queryBuilder, Account $user) {
        $alias = $queryBuilder->getRootAlias();

        $queryBuilder
            ->join("$alias.affaire", "aff")
            ->join("aff.audience", "aud")
            ->andWhere("aud.service IN (:services)")
            ->setParameter("services", $user->getServices());
    }
    public function canCreate($renvoi, Account $user): bool {
        return false;
    }
}
