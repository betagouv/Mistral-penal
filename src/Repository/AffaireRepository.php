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

use App\Entity\Affaire;
use App\Entity\Audience;
use App\Entity\Intervenant;
use App\Entity\Security\Account;
use App\Security\AuthorizationFilterInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Affaire>
 *
 * @method Affaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method Affaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method Affaire[]    findAll()
 * @method Affaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffaireRepository extends ServiceEntityRepository implements AuthorizationFilterInterface
{
    use SortableRepositoryTrait;

    const SORTABLE_GROUP = 'audience_id';
    const SQL_SCHEMA = 'webapp';
    const SQL_NAME = 'affaire';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affaire::class);
    }

    public function getAffaire(int $affaireId, Account $user) {
        $em = $this->getEntityManager();

        $affaire = $em->createQueryBuilder()
            ->select("aud,aff")
            ->from("App\Entity\Affaire", "aff")
            ->join("aff.audience", "aud")
            ->andWhere("aff.id = :affaireId")
            ->andWhere("aud.service IN (:services)")
            ->setParameter("affaireId", $affaireId)
            ->setParameter("services", $user->getServices())
            ->getQuery()
            ->getSingleResult();

        return $affaire;
    }

    public function getFullAffaire(int $affaireId, Account $user): array {
        $em = $this->getEntityManager();

        $affaire = $this->getAffaire($affaireId, $user);

        $relatedAffaires = $em->createQueryBuilder()
            ->select("NEW App\Repository\DTO\RelatedAffaireDTO(a.id, a.numeroParquet, p.prenom1, p.nom, p.raisonSociale, sp.code)")
            ->from("App\Entity\Affaire", "a")
            ->join("a.affairePersonnes", "ap")
            ->join("ap.statut", "sp")
            ->join("ap.personne","p")
            ->where("a.audience = :audience")
            ->setParameter("audience", $affaire->getAudience())
            ->getQuery()
            ->getResult();


        $affaires = [];
        foreach ($relatedAffaires as $aff) {
            $idx = $aff->getNumeroDossier();
            if (!array_key_exists($idx, $affaires)) {
                $affaires[$idx] = [
                    "id" => $aff->getId(),
                    "numeroDossier" => $aff->getNumeroDossier(),
                    "prevenus" => [],
                    "victimes" => [],
                    "juges" => []
                ];
            }

            $collectionId = null;
            if ($aff->isPrevenu()) {
                $collectionId = "prevenus";
            } else if ($aff->isVictime()) {
                $collectionId = "victimes";
            } else if ($aff->isJuge()) {
                $collectionId = "juges";
            }

            if ($collectionId === null) {
                continue ;
            }

            $affaires[$idx][$collectionId][] = [
                "nomComplet" => $aff->getNomComplet()
            ];
        }
        
        $audienceRelated = [
            "id" => $affaire->getAudience()->getId(),
            "affaires" => array_values($affaires)
        ];

        $affNatinfs = $em->createQueryBuilder()
            ->select("an, n, com, dp, dr, f, d")
            ->from("App\Entity\AffaireNatinf", "an")
            ->join("an.natinf", "n")
            ->leftJoin("an.commune", "com")
            ->leftJoin("an.duplicateParent", "dp")
            ->leftJoin("an.duplicateRoot", "dr")
            ->leftJoin("an.fin", "f")
            ->leftJoin("an.debut", "d")
            ->where("an.affaire = :affaire")
            ->setParameter("affaire", $affaire)
            ->getQuery()
            ->getResult();

        $affaire->setAffaireNatinfs($affNatinfs);

        $affNataffs = $em->createQueryBuilder()
            ->select("n")
            ->from("App\Entity\Nataff", "n")
            ->join("n.affaires", "a")
            ->where("a = :affaire")
            ->setParameter("affaire", $affaire)
            ->getQuery()
            ->getResult();

        $affaire->setNataffs($affNataffs);

        $affDecisions = $em->createQueryBuilder()
            ->select("d, dp, ds, mp")
            ->from("App\Entity\Decision", "d")
            ->leftJoin("d.decisionPrevention", "dp")
            ->leftJoin("d.decisionSanction", "ds")
            ->leftJoin("d.modulationPeine", "mp")
            ->where("d.affaire = :affaire")
            ->setParameter("affaire", $affaire)
            ->getQuery()
            ->getResult();

        $affaire->setDecisions($affDecisions);

        $affNoteAudiences = $em->createQueryBuilder()
            ->select("na")
            ->from("App\Entity\NoteAudience", "na")
            ->join("na.auteur", "a")
            ->where("na.affaire = :affaire")
            ->setParameter("affaire", $affaire)
            ->getQuery()
            ->getResult();

        $affaire->setNoteAudiences($affNoteAudiences);

        $affRenvois = $em->createQueryBuilder()
            ->select("r,rm")
            ->from("App\Entity\Renvoi", "r")
            ->leftJoin("r.renvoiMotif", "rm")
            ->where("r.affaire = :affaire")
            ->setParameter("affaire", $affaire)
            ->getQuery()
            ->getResult();

        $affaire->setRenvois($affRenvois);

        $affIntervenants = $em->createQueryBuilder()
            ->select("i")
            ->from("App\Entity\Intervenant", "i")
            ->join("i.affaires", "a")
            ->where("a = :affaire")
            ->setParameter("affaire", $affaire)
            ->getQuery()
            ->getResult();

        $affaire->setIntervenants($affIntervenants);

        $affPersonnes = $em->createQueryBuilder()
            ->select("ap,p,sd,nat,paysNais,civ,mere,comNais,pere,lang,addr,nat2,formJur,sitFam,antJud,catPen,sp, addrPays")
            ->from("App\Entity\AffairePersonne", "ap")
            ->join("ap.personne", "p")
            ->leftJoin("ap.statut", "sp")
            ->leftJoin("p.sansDomicile", "sd")
            ->leftJoin("p.nationalite", "nat")
            ->leftJoin("p.paysNaissance", "paysNais")
            ->leftJoin("p.civilite", "civ")
            ->leftJoin("p.mere", "mere")
            ->leftJoin("p.communeNaissance", "comNais")
            ->leftJoin("p.pere","pere")
            ->leftJoin("p.langueParlee","lang")
            ->leftJoin("p.adresse", "addr")
            ->leftJoin("addr.pays", "addrPays")
            ->leftJoin("p.nationalite2", "nat2")
            ->leftJoin("p.formeJuridique", "formJur")
            ->leftJoin("p.situationFamilliale", "sitFam")
            ->leftJoin("p.antecedentJudiciaire", "antJud")
            ->leftJoin("p.categoriePenale", "catPen")
            ->where('ap.affaire = :affaire')
            ->setParameter("affaire", $affaire)
            ->getQuery()
            ->getResult();

        $affaire->setAffairePersonnes($affPersonnes);

        $natinfPersonnes = $em->createQueryBuilder()
            ->select("np,mp")
            ->from("App\Entity\NatinfPersonne", "np")
            ->leftJoin("np.modaliteParticipation", "mp")
            ->where("np.affaireNatinf IN (:affaireNatinfs)")
            ->setParameter("affaireNatinfs", $affNatinfs)
            ->getQuery()
            ->getResult();

        $natinfMapping = [];

        foreach ($natinfPersonnes as $np) {
            $id = $np->getAffaireNatinf()->getId();
            if (array_key_exists($id, $natinfMapping)) {
                $natinfMapping[$id][] = $np;
            } else {
                $natinfMapping[$id] = [$np];
            }
        }
        foreach ($affNatinfs as $an) {
            if (array_key_exists($an->getId(), $natinfMapping)) {
                $an->setPersonnes($natinfMapping[$an->getId()]);
            }
        }

        return [
            "affaire" => $affaire,
            "audience" => $audienceRelated
        ];
    }

    /**
     * Fonction de chargement des intervenants d'une affaire donné
     *
     * @param Affaire $affaire
     * @return void
     */
    public function initIntervenants(Affaire $affaire): void
    {
        if($affaire->getIntervenants()->count() > 0)
          return;
        /** @var Collection<int, Intervenant> */
        $intervenants = $affaire
          ->getAudience()
          ->getIntervenants()
        ;
        foreach($intervenants as $intervenant)
          $affaire->addIntervenant($intervenant);
        $this->save($affaire, true);
    }

    public function getMotsRapides(Affaire $affaire, Account $account, ?string $word): array
    {
      /** @var Connection $conn */
      $conn = $this->getEntityManager()->getConnection();
      /** @var array $subWords */
      $subWords = ((null !== $word) && mb_strlen($word)) ? explode(" ",trim(preg_replace("/[ ]+/"," ",$word))) : [];
      /** @var array $values */
      $values = ['affaireId' => $affaire->getId(), 'accountId' => $account->getId()];
      /** @var string $sql */
      $sql = "
      SELECT
        i.nom_complet || ' (' || i.role || ')' AS nom_complet,
        i.nom_complet AS id,
        'i' AS type
      FROM webapp.affaire a
      INNER JOIN webapp.audience_intervenant ai ON ai.audience_id = a.audience_id
      INNER JOIN webapp.intervenant i ON i.id = ai.intervenant_id
      WHERE a.id = :affaireId AND #sqlConstraint2#
      UNION
      SELECT
        mr.code || ' - ' || (CASE WHEN LENGTH(mr.libelle) > 50 THEN LEFT(mr.libelle,50)||'...' ELSE mr.libelle END) AS nom_complet,
        mr.libelle AS id,
        'z' AS type
      FROM webapp.mot_rapide mr
      WHERE mr.account_id = :accountId AND #sqlConstraint3#
      ORDER BY nom_complet ASC
      ";
      /** @var array $sqlConstraints2 */
      $sqlConstraints2=["1=1"];
      /** @var array $sqlConstraints3 */
      $sqlConstraints3=["1=1"];
      /**
       * @var int $index
       * @var string $subWord
       */
      foreach($subWords as $index => $subWord) {
        $sqlConstraints2[]="(i.nom_complet ~* :subword$index)";
        $sqlConstraints3[]="(mr.code ~* :subword$index OR mr.libelle ~* :subword$index)";
        $values["subword$index"]=$subWord;
      }

      $sql = str_replace("#sqlConstraint2#", implode(" AND ", $sqlConstraints2), $sql);
      $sql = str_replace("#sqlConstraint3#", implode(" AND ", $sqlConstraints3), $sql);

      $stmt = $conn->prepare($sql);
      $result = $stmt->execute($values);
      /** @var array $output */
      $output = (!is_bool($result)) ? $result->fetchAll() : $stmt->fetchAll();

      /**
       * @author yanroussel
       *
       * hack pour récupérer l'ensemble des personnes
       */
      foreach($affaire->getAffairePersonnes() as $affairePersonne) {
        $tmp=$affairePersonne->getNomPourMotRapide();
        $check = true;
        foreach($subWords as $subWord)
          if(false === strpos(strtolower($tmp['id']), strtolower($subWord)))
            $check = false;
        if(true === $check)
          $output[] = $tmp;
      }
      return $output;
    }

    public function getNextPositionByAudience(Audience $audience): int
    {
        return $this->getNextPositionBySortableGroup(
          self::SORTABLE_GROUP,
          $audience->getId(),
          self::SQL_SCHEMA,
          self::SQL_NAME
        );
    }
    public function save(Affaire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Affaire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function authorizationFilter(QueryBuilder $queryBuilder, Account $user) {
        $alias = $queryBuilder->getRootAlias();

        $queryBuilder
            ->join("$alias.audience", "aud")
            ->andWhere("aud.service IN (:services)")
            ->setParameter("services", $user->getServices());
    }
    public function canCreate($adresse, Account $user): bool {
        return false;
    }
}
