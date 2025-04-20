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
namespace App\Controller\AffaireNatinf;

use App\Entity\AffaireNatinf;
use App\Entity\Personne;
use App\Entity\Commune;
use App\Entity\HorodatageFait;
use App\Entity\Natinf;
use App\Entity\NatinfPersonne;
use App\Entity\OperateurHorodatage;
use App\Entity\Security\Account;
use App\Repository\AffaireNatinfRepository;
use App\Repository\NatinfRepository;
use App\Repository\NatinfPersonneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostRevertDisqualification extends AbstractController {

  private ?EntityManagerInterface $_em=null;
  private ?TranslatorInterface $_trans = null;

  public function __construct(
    EntityManagerInterface $em,
    TranslatorInterface $trans
  ) {
    $this->_em = $em;
    $this->_trans = $trans;
  }

  public function getNatinfPersonneRepository(): ?NatinfPersonneRepository {
    return $this->_em->getRepository(NatinfPersonne::class);
  }

  public function getNatinfRepository(): ?NatinfRepository {
    return $this->_em->getRepository(Natinf::class);
  }

  public function getEntityManager(): ?EntityManagerInterface {
    return $this->_em;
  }

  public function getTranslator(): ?TranslatorInterface {
    return $this->_trans;
  }

  public function getAffaireNatinfRepository(): ?AffaireNatinfRepository {
    return $this->_em->getRepository(AffaireNatinf::class);
  }

  #[Route("/api/affaire_natinfs/v1/{id}/annulation-disqualification", name: "api_affaire_natinfs_revert_disqual_POST", methods: ["POST"], options: ["expose" => true])]
  public function __invoke(?int $id, Request $request)
  {
    $user = $this->getUser();

    $affaireNatinf = $this->getAffaireNatinfRepository()->getAffaireNatinf($id, $user);

    /** @var array $diff */
    $diff = AffaireNatinfRepository::diff($affaireNatinf->getDuplicateRoot(), $affaireNatinf->getDuplicateRoot());
    /** @var EntityManagerInterface $em */
    $em = $this->getEntityManager();
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    $tmp = $content['personnes']??$request->get('personnes',"");
    /** @var array $personnesId */
    $personnesId = explode(",", $tmp);

    /** @var ?AffaireNatinf $newAffaireNatinf */
    $newAffaireNatinf = null;
    foreach($personnesId as $personneId) {
      $personne = $this
        ->getEntityManager()
        ->getRepository(Personne::class)
        ->find($personneId)
      ;

      if(null !== $personne)
        $newAffaireNatinf = $this
          ->getAffaireNatinfRepository()
          ->revertDisqual($affaireNatinf,$personne)
        ;
    }
    $diff['nature'] = AffaireNatinfRepository::NATURE_ANNULATION;

    return new JsonResponse($diff, 200);
  }
}
