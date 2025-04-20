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

use App\Repository\AudienceRepository;
use App\Service\Breadcrumb\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

class ImportController extends AbstractController
{
    #[Route('/mes-imports/mon-calendrier', name: 'app_import_index')]
    public function index(Breadcrumb $breadcrumb, AudienceRepository $ar): Response
    {
        /** @var int $minYear */
        $minYear = 2021;
        /** @var int $maxYear */
        $maxYear = intval(date("Y"));

        // Pilotage du fil d'ariane
        $breadcrumb->add("globals.app_name", null);
        $breadcrumb->add("import.index.title", null);

        $items = $ar->findImportStatus($minYear,$maxYear);
        return $this->render('import/index.html.twig', [
          'breadcrumb' => $breadcrumb,
          'items' => $items,
          'minYear' => $minYear,
          'maxYear' => $maxYear,
        ]);
    }

    #[Route('/mes-imports/mes-audiences/{year}/{month}', name: 'app_import_audience')]
    public function importAudience(Request $request, AudienceRepository $ar, Breadcrumb $breadcrumb): Response
    {
        // Pilotage du fil d'ariane
        $breadcrumb->add("globals.app_name", null);
        $breadcrumb->add("import.index.title", 'app_import_index');
        $breadcrumb->add('import.audience.small_title', null);

        /** @var int $month */
        $month = $request->get('month');
        /** @var int $year */
        $year = $request->get('year');
        /** @var array $audiences */
        $audiences = $ar->getInfosFromImportAudiences($month, $year);
        return $this->render('import/audience.html.twig', [
          'month' => $month,
          'year' => $year,
          'audiences' => $audiences,
          'breadcrumb' => $breadcrumb,
        ]);
    }
}
