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
namespace App\Controller\RepresentantLegal;

use App\Entity\Adresse;
use App\Entity\AffairePersonne;
use App\Entity\Civilite;
use App\Entity\Nationalite;
use App\Entity\Pays;
use App\Entity\Personne;
use App\Entity\RepresentantLegal;
use App\Entity\StatutPersonne;
use App\Repository\AdresseRepository;
use App\Repository\AffairePersonneRepository;
use App\Repository\AffaireRepository;
use App\Repository\PersonneRepository;
use App\Repository\RepresentantLegalRepository;
use App\Repository\StatutPersonneRepository;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

#[AsController]
class AddRepresentant extends AbstractController {

  private ?AdresseRepository $_adr=null;
  private ?RepresentantLegalRepository $_rr=null;
  private ?AffairePersonneRepository $_apr=null;
  private ?StatutPersonneRepository $_spr=null;
  private ?AffaireRepository $_ar=null;
  private ?EntityManagerInterface $_em=null;
  private ?PersonneRepository $_pr=null;

  public function __construct(
    AdresseRepository $adr,
    RepresentantLegalRepository $rr,
    AffaireRepository $ar,
    AffairePersonneRepository $apr,
    PersonneRepository $pr,
    StatutPersonneRepository $spr,
    EntityManagerInterface $em
  )
  {
    $this->_adr = $adr;
    $this->_ar = $ar;
    $this->_spr = $spr;
    $this->_rr = $rr;
    $this->_pr = $pr;
    $this->_apr = $apr;
    $this->_em = $em;
  }

  public function getAdresseRepository(): ?AdresseRepository
  {
    return $this->_adr;
  }

  public function getEntityManager(): ?EntityManagerInterface
  {
    return $this->_em;
  }

  public function getPersonneRepository(): ?PersonneRepository
  {
    return $this->_pr;
  }

  public function getAffaireRepository(): ?AffaireRepository
  {
    return $this->_ar;
  }

  public function getStatutPersonneRepository(): ?StatutPersonneRepository
  {
    return $this->_spr;
  }

  public function getAffairePersonneRepository(): ?AffairePersonneRepository
  {
    return $this->_apr;
  }

  public function getRepresentantLegalRepository(): ?RepresentantLegalRepository
  {
    return $this->_rr;
  }

  #[Route('/api/representant_legals/new', name: 'api_representant_legal_new_POST_collection', methods: ['POST'], options: ["expose" => true])]
  public function __invoke(Request $request): JsonResponse {
    $adr= $this->getAdresseRepository();
    $rr = $this->getRepresentantLegalRepository();
    $apr= $this->getAffairePersonneRepository();
    $spr= $this->getStatutPersonneRepository();
    $pr = $this->getPersonneRepository();
    $em = $this->getEntityManager();
    $ar = $this->getAffaireRepository();

    $user = $this->getUser();

    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?int $representeId */
    $representeId = $content['represente_id']??null;
    /** @var ?int $affaireId */
    $affaireId    = $content['affaire_id']??null;

    /** @var StatutPersonne $statutPersonne */
    $statutPersonne = $spr->findOneBy(['code' => StatutPersonne::CODE_PERSONNE_LIEE]);
    /** @var Affaire $affaire */
    $affaire = $ar->getAffaire($affaireId, $user);
    /** @var AffairePersonne $represente */
    $represente = $apr->find($representeId);
    /** @var Pays $paysNaissance */
    $paysNaissance = $em->getRepository(Pays::class)->findOneBy(['code' => Pays::CODE_FRANCE]);
    /** @var Adresse $adresse */
    $adresse = new Adresse($em);
    $adresse->setPays($paysNaissance);
    $em->persist($adresse);
    $em->flush();

    /** @var Nationalite $nationalite */
    $nationalite = $em->getRepository(Nationalite::class)->findOneBy(['code' => Nationalite::CODE_FRANCE]);
    /** @var Civilite $civilite */
    $civilite = $em->getRepository(Civilite::class)->findOneBy(['code' => Civilite::CODE_MISTER]);
    /** @var Personne $personne */
    $personne = new Personne($em);
    $personne->setAdresse($adresse);
    $personne->setPaysNaissance($paysNaissance);
    $personne->setNationalite($nationalite);
    $personne->setCivilite($civilite);
    $em->persist($personne);
    $em->flush();

    /** @var AffairePersonne $representant */
    $representant = new AffairePersonne();
    $representant->setAffaire($affaire);
    $representant->setStatut($statutPersonne);
    $representant->setPersonne($personne);
    $em->persist($representant);
    $em->flush();

    /** @var RepresentantLegal $rl */
    $rl = new RepresentantLegal();
    $rl->setStatut("");
    $rl->setRepresente($represente);
    $rl->setRepresentant($representant);
    $em->persist($rl);
    $em->flush();

    return new JsonResponse([
      $rl->getId()
    ]);
  }
}
