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
use App\Entity\Security\Account;
use App\Repository\AudienceRepository;
use App\Service\Cassiopee\AudienceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetImport extends AbstractController {
    public function __construct(
        private EntityManagerInterface $em,
        private AudienceService $audienceService,
        private AudienceRepository $audienceRepository,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(Request $request): JsonResponse {
        /** @var array $content */
        $content = json_decode($request->getContent(), true);
        /** @var string|null $strStartAt */
        $strStartAt = $content['date_debut']??$request->get('date_debut',null);
        /** @var string $uid */
        $serviceId = $content['serviceId']??$request->get('serviceId','');
        /** @var \DateTime $startAt */
        $startAt = new \DateTime($strStartAt);
        /** @var Account $user */
        $user = $this->getUser();

        $userServices = [];
        $services = [];
        foreach ($user->getAccountServices() as $ac) {
            $userServices[] = $ac->getService();
        }

        if (!empty($serviceId)) {
            $services = array_values(
                array_filter(
                    $userServices,
                    fn($service) => $service->getid() == $serviceId
                )
            );
        }

        if (count($services) != 1) {
            $services = $userServices;
        }

        $audiences = $this->importAudiences($startAt, $services);

        try {
            $this->em->getConnection()->beginTransaction();

            $this->insertOrUpdateAudiences($audiences, $services);

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

        return new JsonResponse([
            "status" => "OK"
        ]);
    }

    private function importAudiences($startAt, $services): array {
        $this->audienceService->restoreCookies();
        $audiences = $this->audienceService->getAudiences(
            $startAt,
            array_map(fn($service) => ["serviceId" => $service->getIdKsp()], $services)
        );

        return $audiences;
    }

    private function insertOrUpdateAudiences($audiences, $services): void {
        $isr = $this->audienceRepository;
        $ids = array_map(fn($a) => $a["id_cassiopee"], $audiences);

        $audienceEntities = $isr->findByIdKspIn($ids);

        foreach($audiences as $audience) {
            $obj = null;
            foreach ($audienceEntities as $audienceEntity) {
                if ($audienceEntity->getIdKsp() == $audience["id_cassiopee"]) {
                    $obj = $audienceEntity;
                    break ;
                }
            }

            if(null === $obj) {
                $obj = new Audience();
                $obj->setIdKsp($audience["id_cassiopee"]);
            }

            $selectedService = null;
            foreach($services as $service) {
                if ($service->getLabel() === $audience["libelle"]) {
                    $selectedService = $service;
                    break ;
                }
            }

            $obj->setDateMiseAJour(new \DateTime());
            $obj->setDate($audience["date"]);
            $obj->setService($selectedService);
            $obj->setDebut($audience["heure_debut"]);
            $obj->setType($audience["type"] == "collégiale" ? Audience::TYPE_COLLEGIAL : Audience::TYPE_JUGE_UNIQUE);
            $obj->setEvaluatedTime($audience['duree_evaluee']);
            $obj->setEstimatedTime($audience['duree_theorique']);
            $obj->setNumberOfFolders($audience['quantite_affaires']);
            $obj->setMaxNumberOfFolders($audience['quantite_affaires_max']);
            $obj->generatePlaintext($this->translator);
            $this->em->persist($obj);
        }

        $this->em->flush();
        $this->em->clear();
    }
}
