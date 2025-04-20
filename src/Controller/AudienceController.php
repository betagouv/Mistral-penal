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
namespace App\Controller;

use App\Entity\Audience;
use App\Entity\CassiopeeImportJob;
use App\Entity\CassiopeeImportJobStatus;
use App\Entity\Security\Account;
use App\Form\CalendarFilterType;
use App\Message\ImportAudienceMessage;
use App\Repository\AudienceRepository;
use App\Repository\CassiopeeImportJobRepository;
use App\Service\Breadcrumb\Breadcrumb;
use App\Service\Cassiopee\AbstractCassiopeeService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AudienceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private AudienceRepository $audienceRepository
    ){}

    

    #[Route('/mes-audiences', name: 'audience_list')]
    #[Route('/accueil', name: 'homepage', options: ["expose" => true])]
    public function index(Request $request, Breadcrumb $breadcrumb): Response
    {
        $session = $request->getSession();
        /** @var Account $account */
        $account = $this->getUser();
        if($account->hasRole(Account::ROLE_ADMIN_FONC))
          return $this->redirectToRoute('admin_func');

        // Pilotage du fil d'ariane
        $breadcrumb->add("globals.app_name", null);
        $breadcrumb->add("instance_of_service.index.title", null);

        $calendarFilter = $request->get('calendar_filter',[]);
        /** @var ?string $month */
        $month = $calendarFilter['month']??null;
        /** @var ?string $service */
        $serviceId = $calendarFilter['service']??null;
        if(empty($month) && !empty($session->get('month')))
          $month = $session->get('month');

        if(null===$serviceId && !empty($session->get('service')))
          $serviceId = $session->get('service');

        /** @var Form $form */
        $form = $this->createForm(CalendarFilterType::class,null,[
          'month' => $month,
          'user' => $this->getUser(),
          'service' => $serviceId,
        ]);
        $form->handleRequest($request);
        /** @var ?\DateTime $firstDate */
        $firstDate = null;
        if(!empty($month) && preg_match("/[0-9]{4}[-](0[0-9]|1[0-2])/",$month)) {
          $firstDate = new \DateTime($month."-01");
        }
        if($form->isSubmitted()){
          $firstDate = new \DateTime($request->get('calendar_filter')['month']."-01");
          $session->set('month', $month);
          $session->set('service', $serviceId);
        }elseif(null === $firstDate){
          $firstDate = new \DateTime('first day of this month');
        }

        $userServices = [];
        foreach ($account->getAccountServices() as $as) {
            $userServices[] = $as->getService();
        }
        $selectedServices = $userServices;

        if ($serviceId !== null) {
            $selectedServices = array_values(array_filter(
                $userServices,
                fn($s) => $s->getId() == $serviceId
            ));
        }

        if (count($selectedServices) <= 0) {
            $selectedServices = $userServices;
        }

        /** @var array<int, Audience> $ios */
        $ios = $this->entityManager
            ->getRepository(Audience::class)
            ->findByDateAndServices($firstDate,$selectedServices)
            ->getResult()
        ;

        return $this->render('audience/index.html.twig',[
          'breadcrumb' => $breadcrumb,
          'title' => 'instance_of_service.index.title',
          'form'  => $form->createView(),
          'audiences' => $ios
        ]);
    }

    #[Route('/mon-audience/{id}', name: 'audience_show', methods: ['GET'], options: ["expose" => true])]
    public function show(int $id, Request $request, Breadcrumb $breadcrumb, EntityManagerInterface $em): Response
    {
        // Pilotage du fil d'ariane
        $breadcrumb->add("globals.app_name", null);
        $breadcrumb->add("instance_of_service.index.title", 'audience_list');
        $breadcrumb->add("instance_of_service.show.title", null);

        $audience = $this->audienceRepository->getAudience($id, $this->getUser());

        $audience->setDateMiseAJour(new \DateTime());
        $em->flush();

        if($audience->getAffaires()->count() > 0){
          return $this->redirectToRoute('affaire_edit_general',['affaireId' => $audience->getAffaires()->first()->getId()],301);
        }

        return $this->render('audience/show.html.twig', ['audience' => $audience, 'breadcrumb' => $breadcrumb]);
    }

    #[Route('/mon-audience/{id}/import-from-cassiopee', name: "audience_import_from_cassiopee", methods: ['GET'], options: ["expose" => true])]
    public function import(int $id, MessageBusInterface $bus, SessionInterface $session, EntityManagerInterface $em, CassiopeeImportJobRepository $cijp) {
        $audience = $this->audienceRepository->getAudience($id, $this->getUser());

        $importJob = new CassiopeeImportJob();

       $importJob->setStatus(CassiopeeImportJobStatus::NEW);
       $importJob->setProgress(0);
       $em->persist($importJob);
       $em->flush();

        $bus->dispatch(new ImportAudienceMessage(
            $audience->getId(),
            $this->getUser()->getId(),
            $importJob->getId(),
            AbstractCassiopeeService::getCookies($session)
        ));

        return new JsonResponse([
            "message" => "l'importation de l'audience " . $audience->getId() . " a débutée...",
            "importJobId" => $importJob->getId()
        ]);
    }
}
