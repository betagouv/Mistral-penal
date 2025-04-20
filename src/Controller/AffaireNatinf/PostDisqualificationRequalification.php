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
use App\Entity\Commune;
use App\Entity\HorodatageFait;
use App\Entity\Natinf;
use App\Entity\NatinfPersonne;
use App\Repository\AffaireNatinfRepository;
use App\Repository\NatinfRepository;
use App\Repository\NatinfPersonneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostDisqualificationRequalification extends AbstractController {

  use ProcessModificationTrait;
  use ProcessNatinfTrait;
  use ProcessCommuneTrait;
  use ProcessDateTrait;

  public function __construct(
    private EntityManagerInterface $em,
    private TranslatorInterface $trans,
    private LoggerInterface $logger
  ) {}

  protected function getEntityManager():EntityManagerInterface { return $this->em; }

  public function getNatinfPersonneRepository(): ?NatinfPersonneRepository {
    return $this->em->getRepository(NatinfPersonne::class);
  }

  public function getAffaireNatinfRepository(): ?AffaireNatinfRepository {
    return $this->em->getRepository(AffaireNatinf::class);
  }

  private function getNatinfPersonnes(AffaireNatinf $affaireNatinf,Request $request): array
  {
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var array $natinfPersonneIds */
    $natinfPersonneIds = $content['personnes']??$request->get('personnes',[]);
    /** @var array<int, NatinfPersonne> $natinfPersonnes */
    $natinfPersonnes = [];
    foreach($natinfPersonneIds as $natinfPersonneId) {
      $natinfPersonne = $this->getNatinfPersonneRepository()->find($natinfPersonneId);
      if(null !== $natinfPersonne && (false ===$natinfPersonne->isDisqualifie()))
        $natinfPersonnes[]=$natinfPersonne;
    }
    return $natinfPersonnes;
  }

  #[Route("/api/affaire_natinfs/v1/{id}/disqualification-requalification", name: "api_affaire_natinfs_disqual_requal_POST", methods: ["POST"], options: ["expose" => true])]
  public function __invoke(int $id, Request $request)
  {
    $user = $this->getUser();

    $affaireNatinf = $this->getAffaireNatinfRepository()->getAffaireNatinf($id, $user);

    if($affaireNatinf->isLocked()) {
      return new JsonResponse(['errmsg' => 'Au moins une décision a été rendue sur cette infraction. Mise à jour impossible'], 404);
    }
    

    /** @var AffaireNatinf $newAffaireNatinf */
    $newAffaireNatinf = $this
      ->getAffaireNatinfRepository()
      ->duplicate($affaireNatinf)
    ;
    try {
      $this->processNatinf($newAffaireNatinf, $request);
      $this->processCommune($newAffaireNatinf, $request);
      $this->processModification($newAffaireNatinf, $request);
      $this->processDate($newAffaireNatinf, $request);
    }
    catch(\Exception $e) {
      return new JsonResponse(['errmsg' => $e->getMessage()], 404);
    }

    /** @var EntityManagerInterface $em */
    $tab = AffaireNatinfRepository::diff($newAffaireNatinf, $newAffaireNatinf->getDuplicateRoot());
    /** @var string $nature */
    $nature = $tab['nature'];

    /** @var array<int, NatinfPersonne> $natinfPersonnes */
    $natinfPersonnes = $this->getNatinfPersonnes($affaireNatinf, $request);
    if($nature === AffaireNatinfRepository::NATURE_MODIFICATION)
      $natinfPersonnes = $affaireNatinf->getPersonnes();

    if(
      ($nature ===AffaireNatinfRepository::NATURE_REQUALIFICATION)
      &&
      (0 === count($natinfPersonnes))
    )
      return new JsonResponse(['errmsg' => 'aucune personne sélectionnée'], 404);

    $this->em->flush();
    foreach($natinfPersonnes as $natinfPersonne) {
      $newNatinfPersonne = $this
        ->getNatinfPersonneRepository()
        ->duplicate($natinfPersonne, $newAffaireNatinf)
      ;
      $natinfPersonne->setIsDisqualifie(true);
      $this->em->flush();
    }

    $tab = AffaireNatinfRepository::diff($newAffaireNatinf, $newAffaireNatinf->getDuplicateRoot());
    return new JsonResponse($tab);
  }
}
