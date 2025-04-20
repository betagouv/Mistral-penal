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

use App\Entity\Avocat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avocat>
 *
 * @method Avocat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Avocat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Avocat[]    findAll()
 * @method Avocat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvocatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avocat::class);
    }

    public function insertOrUpdate(array $avocats): void
    {
      foreach($avocats as $avocat) {
        $objAvocat = $this->findOneBy(['uid' => $avocat['id']]);
        if(null === $objAvocat) {
          $objAvocat = new Avocat();
          $objAvocat->setUid($avocat['id']);
          $this->save($objAvocat);
        }
        $objAvocat->setNom($avocat['nom']);
        $objAvocat->setPrenom($avocat['prenom']);
        $objAvocat->setAdresse($avocat['adresse']);
        $objAvocat->setBarreau($avocat['barreau']);
        $objAvocat->setEtat($avocat['etat']);
      }
      $this->getEntityManager()->flush();
    }

    public function save(Avocat $avocat, bool $flush=false): void
    {
        $this->getEntityManager()->persist($avocat);
        if(true === $flush)
          $this->getEntityManager()->flush();
    }

    public function findAllByTerm(string $terms,int $offset=0, int $limit=10): array
    {
      $query = [];
      /** formatage de la recherche */
      $tab=["1=1"];
      $conn = $this->getEntityManager()->getConnection();
      if(!empty($terms)) {
        $terms = preg_replace("/([ ]+|-)/i"," ",$terms);
        array_map(function($item)use(&$tab,$conn){
            $item=iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $item);
            $item = mb_strtolower($item);
            $item = $conn->quote($item);
            $tab[]="(n.nom ~* $item OR n.prenom ~* $item)";
        },explode(" ",$terms));
      }
      $data = implode(' AND ', $tab);
      $sql = "
      SELECT
        n.id,
        n.nom,
        n.prenom,
        n.adresse,
        n.barreau,
        COUNT(*)OVER() count
      FROM webapp.avocat n
      WHERE $data
      ORDER BY LENGTH(n.nom) ASC,LENGTH(n.prenom) ASC
      OFFSET $offset
      LIMIT $limit
      ";

      $stmt = $this->getEntityManager()->getConnection()->query($sql);
      $query = $stmt->fetchAll();
      $count = !empty($query[0]) && !empty($query[0]['count']) ? $query[0]['count'] : 0;
      foreach($query as $index => $item)
        unset($query[$index]['count']);

      return array_merge(['avocats' => $query], ['count'=> $count]);
    }
}
