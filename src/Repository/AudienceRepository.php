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
use App\Entity\Audience;
use App\Security\AuthorizationFilterInterface;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Audience>
 *
 * @method Audience|null find($id, $lockMode = null, $lockVersion = null)
 * @method Audience|null findOneBy(array $criteria, array $orderBy = null)
 * @method Audience[]    findAll()
 * @method Audience[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AudienceRepository extends ServiceEntityRepository implements AuthorizationFilterInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Audience::class);
    }

    public function getAudience(int $id, Account $user): ?Audience {
        $query = $this->createQueryBuilder("aud")
            ->andWhere("aud.id = :audienceId")
            ->setParameter("audienceId", $id);

        $this->authorizationFilter($query, $user);

        return $query->getQuery()->getSingleResult();
    }

    public function save(Audience $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Audience $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getInfosFromImportAudiences(int $month, int $year): array {
      $em = $this->getEntityManager();
      $conn = $em->getConnection();
      $sql = "
      SELECT
        aud.id,
        EXTRACT(day FROM aud.date) AS day,
        aud.plaintext,
        aud.id_ksp,
        count(aff.*) AS count_affaire,
        aud.date_dernier_import
      FROM webapp.audience aud
      LEFT JOIN webapp.affaire aff ON aff.audience_id = aud.id
      WHERE EXTRACT(month FROM aud.date) = $month and EXTRACT(year FROM aud.date) = $year
      GROUP BY
        EXTRACT(day FROM aud.date),
        aud.date,
        aud.plaintext,
        aud.id_ksp,
        aud.date_dernier_import
      ORDER BY aud.date ASC, aff.numeroParquet ASC
      ";

      $stmt = $conn->query($sql);
      $records = $stmt->fetchAll();
      $output=[];
      for($i=1;$i<=31;$i++)
        $output[$i]=[];

      foreach($records as $record) {
        $output[$record['day']][]=$record;
      }
      return $output;
    }
    public function findImportStatus(int $minYear, int $maxYear): array {
      $em = $this->getEntityManager();
      $conn = $em->getConnection();
      $sql = "
      SELECT
        u.year,
        u.month,
        MAX(u.min_date) AS min_date,
        MAX(u.max_date) AS max_date,
        MAX(u.maj) AS maj,
        MAX(u.cpt) AS cpt
      FROM (
      	SELECT
        	extract(year from date) AS year,
        	extract(month from date) AS month,
        	min(date) AS min_date,
        	max(date) AS max_date,
        	max(aud.date_mise_a_jour) AS maj,
        	count(*) AS cpt
        FROM webapp.audience aud
        WHERE extract(year FROM aud.date) BETWEEN $minYear AND $maxYear
        GROUP BY extract(year from date), extract(month from date)
        UNION
        SELECT
          c AS year,
          d AS month,
          null AS min_date,
          null AS max_date,
          null AS maj,
          0 AS cpt
        FROM generate_series($minYear,$maxYear) c, generate_series(1,12) d
      ) u
      GROUP BY u.year, u.month
      ORDER BY u.year ASC, u.month ASC";
      $stmt = $conn->query($sql);
      return $stmt->fetchAll();
    }

    public function findByDateAndServices(
        \DateTime $firstDay,
        ?array $services
    ): Query {
        $lastDay = (clone $firstDay)->add(new \DateInterval('P1M'))->modify('first day of this month');
        $lastDay->sub(new \DateInterval('PT1S'));

        $qb = $this->createQueryBuilder('a')
            ->select(
                'NEW App\Controller\Audience\IndexAudienceDTO(
                    a.id,
                    a.date,
                    a.finAudience,
                    a.idKsp,
                    a.debut,
                    s.label,
                    a.dateDernierImport,
                    a.numberOfFolders,
                    a.dateMiseAJour,
                    a.type
                )'
            )
            ->join('a.service', 's');

        $whereClause = $qb->expr()->andX();
        $whereClause = $whereClause->add('a.date BETWEEN :firstDay AND :lastDay');
        $whereClause = $whereClause->add('a.service IN (:services)');

        $qb = $qb->where($whereClause);
        $qb = $qb->orderBy("a.date", "ASC");

        $qb = $qb
            ->setParameter('services', $services)
            ->setParameter('firstDay', $firstDay)
            ->setParameter('lastDay', $lastDay);

        return $qb->getQuery();
    }


    public function findByDateAndUser(
      \DateTime $firstDay,
      Account $user,
      ?string $serviceId=null
    ): Query {
      $lastDay = (clone $firstDay)->add(new \DateInterval('P1M'))->modify('first day of this month');
      $lastDay->sub(new \DateInterval('PT1S'));

      /**
       * @author yanroussel
       *         désactivation des jointures ci-dessous car trop consommatrices
       *         en ressources
       */
       # [dec := LEFT JOIN a.decisions dec]
       # [dop := LEFT JOIN debut.operateur dop]
       # [fop := LEFT JOIN fin.operateur fop]
       # [natinf := LEFT JOIN an.natinf natinf]
       # [v := LEFT JOIN natinf.versions v]
      /** @var string $dql */
      $dql    = "
      SELECT
        a, a_int, adr, adr_pays, ap,
        an, civ, debut, fin,
        ios, i, n, n_com, nat,
        p, pn, ps, sess, st
      FROM App\Entity\Audience ios
      LEFT JOIN ios.affaires a
      LEFT JOIN a.intervenants a_int
      LEFT JOIN a.affaireNatinfs an
      LEFT JOIN an.personnes ps
      LEFT JOIN an.commune n_com
      LEFT JOIN an.debut debut
      LEFT JOIN an.fin fin
      LEFT JOIN a.affairePersonnes ap
      LEFT JOIN ap.statut st
      LEFT JOIN ap.personne p
      LEFT JOIN p.civilite civ
      LEFT JOIN p.nationalite nat
      LEFT JOIN p.paysNaissance pn
      LEFT JOIN p.adresse adr
      LEFT JOIN adr.pays adr_pays
      LEFT JOIN a.nataffs n
      LEFT JOIN ios.intervenants i
      LEFT JOIN ios.sessions sess
      INNER JOIN ios.service s
      INNER JOIN s.accountServices a_s
      INNER JOIN a_s.account u
      WHERE
        ios.date BETWEEN :firstDay AND :lastDay AND
        u = :user
      ";
      if(!empty($serviceId))
        $dql.=" AND s.idKsp = :serviceIdKsp ";
      $query = $this
        ->getEntityManager()
        ->createQuery($dql)
        ->setParameter('firstDay', $firstDay)
        ->setParameter('lastDay', $lastDay)
        ->setParameter('user', $user)
      ;

      if(!empty($serviceId))
        $query->setParameter('serviceIdKsp', $serviceId);

      return $query;
    }

    public function findByIdKspIn(array $ids): array {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT a FROM App\Entity\Audience a WHERE a.idKsp IN (:ids)')
            ->setParameter("ids", $ids)
            ->getResult();
    }

    public function findOutdated($delai): array
    {
        $date = new DateTime();
        $date->modify(sprintf('- %d hours', $delai));

        return $this->createQueryBuilder('a')
            ->andWhere('a.date < :date')
            ->orWhere('a.date = :date AND a.debut < :hours')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('hours', $date->format('H:i'))
            ->getQuery()
            ->getResult()
        ;
    }
    public function authorizationFilter(QueryBuilder $queryBuilder, Account $user) {
        $alias = $queryBuilder->getRootAlias();

        $queryBuilder 
            ->andWhere("$alias.service IN (:services)")
            ->setParameter("services", $user->getServices());
    }

    /**
     * 
     * @param T $object
     * @param \App\Entity\Security\Account $user
     * @return bool 
     */
    public function canCreate($object, Account $user): bool {
        return false;
    }
}
