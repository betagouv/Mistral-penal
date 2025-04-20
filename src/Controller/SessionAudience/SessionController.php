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
namespace App\Controller\SessionAudience;

use App\Entity\Audience;
use App\Entity\SessionAudience;
use App\Repository\AudienceRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class SessionController extends AbstractController {
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private AudienceRepository $audienceRepository)
    {
    }

    #[Route('/sessions/audiences/{audienceId}', name: 'session_audience_started', methods: ['GET'], options: ["expose" => true])]
    public function index(int $audienceId): Response
    {
        $audience = $this->audienceRepository->getAudience($audienceId, $this->getUser());

        $session = $this->entityManager
                    ->getRepository(SessionAudience::class)
                    ->findBy([
                        'account' => $this->getUser(),
                        'audience' => $audience
                    ],
                    ['debutSession' => 'DESC']
                );
        $data = [
            'data' => [
                'debutAudience' => $audience->getDebutAudience(),
                'finAudience' => $audience->getFinAudience(),
                'session' => null
            ]
        ];
        $status = Response::HTTP_OK;

        if(count($session) == 0 && $audience->getFinAudience() == null){
            $status = Response::HTTP_NO_CONTENT;
        }

        if(count($session) > 0){
            if($session[0]->getFinSession() == null){
                $data['data']['session'] = $session[0];
            }
        }

        return new Response(
            $this->serializer->serialize($data, 'json', ['groups' => 'read']),
            $status,
            ['Content-type' => 'application/json']
        );
    }


    #[Route('/audiences/{audienceId}/start-end-save', name: 'audience_start_end_save', methods: ['POST'], options: ["expose" => true])]
    public function save(
        int $audienceId,
        Request $request
    ): Response {
        $audience = $this->audienceRepository->getAudience($audienceId, $this->getUser());

        $sessions = $this->entityManager
                    ->getRepository(SessionAudience::class)
                    ->findBy([
                        'account' => $this->getUser(),
                        'audience' => $audience
                    ],
                    ['debutSession' => 'ASC']
                );

        $start = new DateTime(filter_var($request->request->get('debutAudience'), FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $end = new DateTime(filter_var($request->request->get('finAudience'), FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if($start >= $end || $end <= $start){

            $responseData = [
                'error' => "Erreur l'heure de début doit être inférieur à l'heure de fin, et l'heure de fin supérieur à l'heure de début",
                'data' => [
                    'audience' => $audience->getId(),
                    'debutAudience' => $audience->getDebutAudience(),
                    'finAudience' => $audience->getFinAudience(),
                ]
            ];
    
            return new Response(
                $this->serializer->serialize($responseData, 'json', ['groups' => 'read']),
                Response::HTTP_BAD_REQUEST,
                ['Content-type' => 'application/json']
            );
        }

        $audience->setFinAudience($end);
        $i = 0;
        foreach($sessions as $session){

            if($session->getFinSession() <= $start){
                $this->entityManager->remove($session);
                unset($sessions[$i]);
            }

            if($session->getDebutSession() <=  $start && $session->getFinSession() > $start){
                $session->setDebutSession($start);
            }

            if($session->getDebutSession() > $start && $i==array_key_first($sessions) ){
                $session->setDebutSession($start);
            }

            if($session->getDebutSession() > $end){
                $this->entityManager->remove($session);
                unset($sessions[$i]);
            }

            if($session->getDebutSession() <  $end && $session->getFinSession() >= $end){
                $session->setFinSession($end);
            }

            if($session->getFinSession() < $end && $i == array_key_last($sessions)){
                $session->setFinSession($end);
            }

            $i++;
        }

        if(count($sessions)==0){
            $session = new SessionAudience();
            $session->setDebutSession($start);
            $session->setFinSession($end);
            $session->setAudience($audience);
            $session->setAccount($this->getUser());
            $this->entityManager->persist($session);
        }


        $this->entityManager->flush();
        $sessions = array_values($sessions);

        $responseData = [
            'message' => 'Donnée(s) sauvegardée(s)',
            'data' => [
                'audience' => $audience->getId(),
                'debutAudience' => $sessions[0]->getDebutSession(),
                'finAudience' => $audience->getFinAudience(),
            ]
        ];

        return new Response(
            $this->serializer->serialize($responseData, 'json', ['groups' => 'read']),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );
    }
  
    #[Route('/mon-audience/{audienceId}/close', name: 'audience_close', methods: ['GET'], options: ["expose" => true])]
    public function close(
        int $audienceId
    ): Response {
        $audience = $this->audienceRepository->getAudience($audienceId, $this->getUser());

        $date = new DateTime();
        $session = $this->entityManager->getRepository(SessionAudience::class)->findOneBy(
          [
              'audience' => $audience,
              'finSession' => null,
              'account' => $this->getUser()
          ],
          [
            'id' => 'DESC'
          ]
        );

        if($session) $session->setFinSession($date);
        $audience->setFinAudience($date);
        $this->entityManager->flush();

        $responseData = [
            'message' => 'audience clôturée',
            'data' => $audience
        ];

        return new Response(
            $this->serializer->serialize($responseData, 'json', ['groups' => 'read']),
            200,
            ['Content-type' => 'application/json']
        );
    }
}
