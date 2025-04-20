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

use App\Entity\Affaire;
use App\Repository\AffaireRepository;
use App\Service\Document\DocumentNote;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class NoteAudienceController extends AbstractController
{
    public function __construct(private AffaireRepository $affaireRepository) {}

    #[Route('/note-audience/{id}/export-pdf', name: 'app_note_audience_export_pdf')]
    public function exportPdf(int $id, DocumentNote $documentNote): BinaryFileResponse
    {
        $affaire = $this->affaireRepository->getAffaire($id, $this->getUser());

        $filename = $documentNote->create($affaire);
        $filenamePdf = preg_replace("/^(.*)\/([^\/]+)[.]odt$/", "$2.pdf", $filename);
        $outdir = preg_replace("/^(.*)\/(?<filename>.+[.]odt)$/", "$1", $filename);
        $outdir = "/www-data-tmp";
        /**
         * @author yanroussel
         *         Exécution en shell_exec car il y'a nécessité d'utiliser une commande chainée (impossible avec Process sf)
         */
        $output = shell_exec(
            implode(" && ", [
                "export HOME=/var/www",
                "/usr/bin/soffice --headless --convert-to pdf:\"writer_pdf_Export:SelectPdfVersion=3\" --outdir $outdir $filename"
            ])
        );
        $response = new BinaryFileResponse("$outdir/$filenamePdf");
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        return $response;
    }
    #[Route('/note-audience/{id}/export', name: 'app_note_audience_export')]
    public function export(int $id, DocumentNote $documentNote): BinaryFileResponse
    {
        $affaire = $this->affaireRepository->getAffaire($id, $this->getUser());

        $filename = $documentNote->create($affaire);

        $response = new BinaryFileResponse($filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        return $response;
    }
}
