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
namespace App\MessageHandler;
use App\Controller\ImportIteratif\InsertOrUpdateService;
use App\Entity\CassiopeeImportJobStatus;
use App\Message\ImportAudienceMessage;
use App\Repository\AudienceRepository;
use App\Repository\CassiopeeImportJobRepository;
use App\Repository\Security\AccountRepository;
use App\Service\Cassiopee\AffaireService;
use App\Service\Cassiopee\AudienceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ImportAudienceMessageHandler
{
    private int $progress = 0;
    private int $affaireProgressStep = 0;

    public function __construct(
        private AudienceService $audienceService,
        private AffaireService $affaireService,
        private AudienceRepository $audienceRepository,
        private AccountRepository $accountRepository,
        private CassiopeeImportJobRepository $importJobRepository,
        private InsertOrUpdateService $insertOrUpdate,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    private function initImportJob($importJob): void {
        $importJob->setProgress(0);
        $importJob->setStatus(CassiopeeImportJobStatus::RUNNING);
        $this->em->persist($importJob);
        $this->em->flush();
    }

    private function saveProgress($importJob) {
        $importJob->setProgress($this->progress);
        $this->em->persist($importJob);
        $this->em->flush();
    }

    private function importJobAudienceFetched($importJob, $audienceDetails): void {
        $this->progress = 10;
        $this->saveProgress($importJob);

        $this->affaireProgressStep = count($audienceDetails["affaires"]) > 0 ? 90 / count($audienceDetails["affaires"]) : 0;
    }

    private function importJobAffaireFetched($importJob) {
        $this->progress += $this->affaireProgressStep;
        $this->saveProgress($importJob);
    }

    private function importJobDone($importJob) {
        $this->progress = 100;
        $importJob->setStatus(CassiopeeImportJobStatus::DONE);
        $this->saveProgress($importJob);
    }

    public function __invoke(ImportAudienceMessage $message)
    {
        $audienceId = $message->getAudienceId();
        $accountId = $message->getAccountId();
        $cookies = $message->getCookies();
        $importJobId = $message->getImportJobId();

        $this->audienceService->setCookies($cookies);
        $this->affaireService->setCookies($cookies);

        $audience = $this->audienceRepository->find($audienceId);
        $account = $this->accountRepository->find($accountId);
        $importJob = $this->importJobRepository->find($importJobId);

        $this->initImportJob($importJob);

        $audienceDetails = $this->audienceService->getAudienceDetails(
            $audience->getIdKsp()
        );

        $this->importJobAudienceFetched($importJob, $audienceDetails);


        [
            "intervenants" => $intervenants,
            "audience" => $audience,
        ] = $this->insertOrUpdate->insert_or_update_audience(
            $account,
            $this->em,
            $audienceDetails
        );

        $getAffaireDetailsFunc = function ($affaire) use (&$importJob) {
            $affaireDetails = $this->affaireService->getAffaireDetails(
                $affaire["id_ksp_affaire"],
                $affaire["id_ksp_parquet"],
                $affaire["type"],
                $affaire["numero_parquet"]
            );

            $this->importJobAffaireFetched($importJob);

            return array_merge($affaire, $affaireDetails);
        };

        $affaireFibers = array_map(function ($affaire) use (
            &$getAffaireDetailsFunc
        ) {
            $fiber = new \Fiber($getAffaireDetailsFunc);
            $fiber->start($affaire);
            return $fiber;
        }, $audienceDetails["affaires"]);

        $completedFibers = [];
        while ($affaireFibers) {
            foreach ($affaireFibers as $idx => $fiber) {
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                } elseif ($fiber->isTerminated()) {
                    $completedFibers[] = $fiber;
                    unset($affaireFibers[$idx]);
                }
            }
        }

        $affaires = array_map(
            fn($fiber) => $fiber->getReturn(),
            $completedFibers
        );

        $this->logger->info(print_r($affaires, true));

        array_map(
            fn($affaire) => $this->insertOrUpdate->insert_or_update_affaire(
                $this->em,
                $audience,
                $affaire,
                $intervenants
            ),
            $affaires
        );

        $this->importJobDone($importJob);
    }
}
