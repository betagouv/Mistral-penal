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
use App\Repository\AudienceRepository;
use App\Service\Document\DocumentsManager;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;


class DocumentController extends AbstractController
{
    public function __construct(private AudienceRepository $audienceRepository) {}


    #[Route('/audience/{id}/documents/export', name: 'app_audiences_documents_export', options: ["expose" => true])]
    public function export(int $id, DocumentsManager $documentManager)
    {
        $audience = $this->audienceRepository->getAudience($id, $this->getUser());
        $zipFile = $documentManager->createArchive($audience);
        $slugger = new AsciiSlugger();
        $date = new DateTime();
        $trameFilename = "Audience#date#-#debut#-#service#-export#export#";
        $filename = $slugger->slug(
            str_replace([
                "#date#",
                "#debut#",
                "#service#",
                "#export#",
                ],[
                $audience->getDate()->format("dmY"),
                str_replace(":",'h',$audience->getDebut()),
                $audience->getService()->getLabel(),
                $date->format("dmYH\hi"),
                ],
                $trameFilename
            )
        )->lower().'.zip';
        $response = new BinaryFileResponse($zipFile);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
