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
namespace App\Controller\Audience;

use App\Entity\Audience;
use App\Entity\SessionAudience;
use App\Repository\AffaireRepository;
use App\Repository\AudienceRepository;
use App\Repository\SessionAudienceRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class PostSessionAudienceItem extends AbstractController {

  private ?EntityManagerInterface $_em=null;
  private ?AffaireRepository $_ar = null;
  private ?TranslatorInterface $_trans = null;
  private ?SerializerInterface $_serializer = null;

  public function __construct(
    AffaireRepository $ar,
    EntityManagerInterface $em,
    TranslatorInterface $trans,
    SerializerInterface $serializer,
    private AudienceRepository $audienceRepository,
    private SessionAudienceRepository $sessionAudienceRepository
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
  #[Route('/mon-audience/{audienceId}/session', name: 'audience_session_post_item', methods: ['POST'], options: ["expose" => true])]
  #[Route('/mon-audience/{audienceId}/session/{sessionId}', name: 'audience_session_update_item', methods: ['POST'], options: ["expose" => true])]
  public function __invoke(
    int $audienceId,
    Request $request,
    int $sessionId = null
  ) {

    $errors = null;
    $message = null;
    $status = Response::HTTP_OK;
    $routeParams = $request->attributes->get('_route_params');

    $audience = $this->audienceRepository->getAudience($audienceId, $this->getUser());
    $session = null;

    if ($sessionId !== null) {
        $session = $this->sessionAudienceRepository->getSessionAudience($sessionId, $this->getUser());
    }

    if(!array_key_exists('session', $routeParams)){
        $session = null;
        $session = $this->_em->getRepository(SessionAudience::class)->findOneBy(
            [
                'audience' => $audience,
                'finSession' => null,
                'account' => $this->getUser()
            ]
        );
    }

    if($session){
        $session->setFinSession(new DateTime($request->request->get('finSession')));
    }

    if(!$session){
        $session = new SessionAudience();
        $session->setAccount($this->getUser());
        $session->setAudience($audience);
        $this->_em->persist($session);
    }

    $this->_em->flush();
    $message = "enregistrement ok";


    $responseData = [
      'errors' => $errors,
      'message' => $message,
      'data' => $session
    ];

    return new Response(
        $this->_serializer->serialize($responseData, 'json', ['groups' => 'read']),
        $status,
        ['Content-type' => 'application/json']
    );
  }
}
