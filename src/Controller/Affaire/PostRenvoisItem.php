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
use App\Entity\Renvoi;
use App\Form\RenvoiType;
use App\Repository\AffaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PostRenvoisItem extends AbstractController {

  private ?EntityManagerInterface $_em=null;
  private ?AffaireRepository $_ar = null;
  private ?TranslatorInterface $_trans = null;

  public function __construct(
    AffaireRepository $ar,
    EntityManagerInterface $em,
    TranslatorInterface $trans,
  ) {
    $this->_ar = $ar;
    $this->_em = $em;
    $this->_trans = $trans;
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
  #[Route('/mon-affaire/{affaireId}/renvois', name: 'affaire_renvois_post_item', methods: ['POST'], options: ["expose" => true])]
  #[Route('/mon-affaire/{affaireId}/renvois/{renvoi}', name: 'affaire_renvois_update_item', methods: ['POST'], options: ["expose" => true])]
  public function __invoke(
    int $affaireId,
    Request $request,
    Renvoi $renvoi = null
  ) {
    $message = null;
    $data = null;
    $status = 200;

    $affaire = $this->getAffaireRepository()->getAffaire($affaireId, $this->getUser());

    if($request->request->get('renvoi')['id'] == ''){
      $renvoi = new Renvoi();
    }

    $formRenvoi = $this->createForm(RenvoiType::class, $renvoi, ['affaire' => $affaire]);
    $formRenvoi->handleRequest($request);

    if ($formRenvoi->isSubmitted() && $formRenvoi->isValid()) {

      $renvoi = $formRenvoi->getData();
      $this->_em->persist($renvoi);
      $this->_em->flush();

      $personnesStr = '';
      $personnesStrId = '';
      $i = 0;
      foreach($renvoi->getAffairePersonnes() as $affairePersonne){
        $personnesStr .= $affairePersonne->getPersonne()->getNomComplet();
        $personnesStrId .= $affairePersonne->getPersonne()->getId().',';
        $i++;
        if($i < count($renvoi->getAffairePersonnes())){
          $personnesStr .= ' - ';
        }
      }

      $message = "enregistrement ok";
      $date = $renvoi->getDate();
      $date->setTimeZone(new \DateTimeZone('Europe/Paris'));
      $data = [
        'id' => $renvoi->getId(),
        'date' => $date->format('d/m/Y H:i'),
        'renvoiMotif' => $renvoi->getRenvoiMotif()->getLibelle(),
        'renvoiMotifId' => $renvoi->getRenvoiMotif()->getId(),
        'affairePersonnes' => $personnesStr,
        'affairePersonnesId' => $personnesStrId
      ];
    }else{
      $message = "erreur enregistrement";
      $status = 400;
      $errors = "";
      foreach($formRenvoi->getErrors(true) as $error){
        $errors .= (implode(', ', $error->getMessageParameters()));
        $errors .= " ".$error->getMessage();
        $errors .= " /";
      }
      $data = $errors;

    }

    return new JsonResponse(
        [
          'message' => $message,
          'data' => $data
        ],
        $status
    );
  }
}
