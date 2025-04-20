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
namespace App\Controller\Affaire;

use App\Entity\Security\Account;
use App\Entity\Affaire;
use App\Entity\Personne;
use App\Entity\AffairePersonne;
use App\Entity\NatinfPersonne;
use App\Entity\StatutPersonne;
use App\Form\AffairePersonne\PrevenuType;
use App\Form\AffaireType;
use App\Repository\AffaireRepository;
use App\Repository\NatinfPersonneRepository;
use App\Repository\StatutPersonneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostAjoutVictime extends AbstractController {

  private ?EntityManagerInterface $_em=null;
  private ?AffaireRepository $_ar = null;
  private ?NatinfPersonneRepository $_npr = null;
  private ?StatutPersonneRepository $_spr = null;
  private ?TranslatorInterface $_trans = null;

  public function __construct(
    AffaireRepository $ar,
    EntityManagerInterface $em,
    NatinfPersonneRepository $npr,
    StatutPersonneRepository $spr,
    TranslatorInterface $trans
  ) {
    $this->_npr = $npr;
    $this->_spr = $spr;
    $this->_ar = $ar;
    $this->_em = $em;
    $this->_trans = $trans;
  }

  public function getStatutPersonneRepository(): ?StatutPersonneRepository {
    return $this->_spr;
  }

  public function getNatinfPersonneRepository(): ?NatinfPersonneRepository {
    return $this->_npr;
  }

  public function getEntityManager(): ?EntityManagerInterface {
    return $this->_em;
  }

  public function getTranslator(): ?TranslatorInterface {
    return $this->_trans;
  }

  public function getAffaireRepository(): ?AffaireRepository {
    return $this->_ar;
  }

  public function __invoke(
    int $id,
    Request $request
  ) {
    $user = $this->getUser();

    $affaire = $this->getAffaireRepository()->getAffaire($id, $user);

    /** @var EntityManagerInterface $em */
    $em = $this->getEntityManager();
    /** @var StatutPersonne $statut */
    $statut = $this->getStatutPersonneRepository()->findOneBy(['code' => StatutPersonne::CODE_VICTIME]);
    /** @var AffairePersonne $affairePersonne */
    $affairePersonne = new AffairePersonne();
    $affairePersonne->setStatut($statut);
    $em->persist($affairePersonne);

    $personne = new Personne($em);
    $affairePersonne->setPersonne($personne);
    $em->persist($personne);

    $affaire->addAffairePersonne($affairePersonne);

    $affaireNatinfs = $affaire->getAffaireNatinfs();
    foreach($affaireNatinfs as $affaireNatinf) {
      $natinfPersonne = new NatinfPersonne();
      $natinfPersonne->setPersonne($personne);
      $natinfPersonne->setStatut(StatutPersonne::CODE_VICTIME);
      $natinfPersonne->setAffaireNatinf($affaireNatinf);
      $this->getNatinfPersonneRepository()->save($natinfPersonne, true);
    }

    $this->getAffaireRepository()->save($affaire, true);

    // $formAffairePersonne = $this->createForm(PrevenuType::class, $affairePersonne);
    // $formAffaire = $this->createForm(AffaireType::class, $affaire, ['affaire' => $affaire]);
    // $view = $this->render('affaire/_details_informations.html.twig', [
    //   'formAffaire' => $formAffaire->createView(),
    // ]);

    return new JsonResponse(['id' => $affairePersonne->getId()]);
  }
}
