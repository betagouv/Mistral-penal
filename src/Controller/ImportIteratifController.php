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

use App\Form\ImportIteratifType;
use App\Service\Breadcrumb\Breadcrumb;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ImportIteratifController extends AbstractController
{
    #[Route('/import-iteratif', name: 'app_import_iteratif', options: ['expose' => true])]
    public function index(
      Request $request,
      Breadcrumb $breadcrumb,
      EntityManagerInterface $em
    ): Response
    {
        // Pilotage du fil d'ariane
        $breadcrumb->add("globals.app_name", null);
        $breadcrumb->add("import.iteratif.title", null);

        $day = (string)$request->get('day','');
        $fullmonth = (string)$request->get('fullmonth','');
        $id = (string)$request->get('id');

        /** @var Form $form */
        $form = $this->createForm(ImportIteratifType::class,null,['id' => $id, 'day' => $day, 'fullmonth' => $fullmonth]);
        $form->handleRequest($request);

        return $this->render('import_iteratif/index.html.twig', [
          'breadcrumb' => $breadcrumb,
          'form' => $form->createView(),
        ]);
    }
}
