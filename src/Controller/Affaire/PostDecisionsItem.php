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

use App\Entity\Affaire;
use App\Entity\Decision;
use App\Form\DecisionType;
use App\Repository\AffaireRepository;
use App\Repository\DecisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class PostDecisionsItem extends AbstractController {

  private ?EntityManagerInterface $_em=null;
  private ?AffaireRepository $_ar = null;
  private ?TranslatorInterface $_trans = null;
  private ?SerializerInterface $_serializer = null;

  public function __construct(
    AffaireRepository $ar,
    EntityManagerInterface $em,
    TranslatorInterface $trans,
    SerializerInterface $serializer,
    private DecisionRepository $decisionRepository,
    private LoggerInterface $logger
  ) {
    $this->_ar = $ar;
    $this->_em = $em;
    $this->_trans = $trans;
    $this->_serializer = $serializer;
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
  #[Route('/mon-affaire/{affaireId}/decisions', name: 'affaire_decisions_post_item', methods: ['POST'], options: ["expose" => true])]
  #[Route('/mon-affaire/{affaireId}/decisions/{decisionId}', name: 'affaire_decisions_update_item', methods: ['POST'], options: ["expose" => true])]
  public function __invoke(
    int $affaireId,
    int $decisionId = null,
    Request $request
  ) {

    $errors = null;
    $message = null;
    $data = null;
    $status = Response::HTTP_OK;

    $affaire = $this->getAffaireRepository()->getAffaire($affaireId, $this->getUser());

    $decision = null; 
    if ($decisionId) {
        $decision = $this->decisionRepository->getDecision($decisionId, $this->getUser());
    } else {
        $decision = new Decision();
    }

    $formDecision = $this->createForm(DecisionType::class, $decision, ['affaire' => $affaire]);
    $formDecision->handleRequest($request);

    if ($formDecision->isSubmitted() && $formDecision->isValid()) {

      $decision = $formDecision->getData();

      if($decision->getAffaireNatinfs()->count() == 0){
          $errors = "Vous devez sélectionner au moins une natinf";
          $message = "erreur enregistrement";
          $status = Response::HTTP_BAD_REQUEST;
      }else{
          $this->_em->persist($decision);
          $this->_em->flush();
          $data = $decision;
          $message = "enregistrement ok";
      }

      

    }else{
      $message = "erreur enregistrement";
      $status = Response::HTTP_BAD_REQUEST;
      $errors = "";
      foreach($formDecision->getErrors(true) as $error){
        $errors .= (implode(', ', $error->getMessageParameters()));
        $errors .= " ".$error->getMessage();
        $errors .= " /";
      }
    }

    $responseData = [
      'errors' => $errors,
      'message' => $message,
      'data' => $data,
    ];

    return new Response(
        $this->_serializer->serialize($responseData, 'json', ['groups' => 'read']),
        $status,
        ['Content-type' => 'application/json']
    );
  }
}
