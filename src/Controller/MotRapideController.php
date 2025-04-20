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

use App\Entity\MotRapide;
use App\Form\MotRapideType;
use App\Repository\MotRapideRepository;
use App\Service\Breadcrumb\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mot-rapide')]
class MotRapideController extends AbstractController
{
    #[Route('/ma-liste', name: 'app_mot_rapide_index', methods: ['GET'])]
    public function index(MotRapideRepository $motRapideRepository, Breadcrumb $breadcrumb): Response
    {
      $user = $this->getUser();

      // Pilotage du fil d'ariane
      $breadcrumb->add("globals.app_name", null);
      $breadcrumb->add("account.index.title", null);
      $breadcrumb->add("mot_rapide.index.title", null);

        return $this->render('mot_rapide/index.html.twig', [
            'mot_rapides' => $motRapideRepository->findBy(["account" => $user]),
            'breadcrumb' => $breadcrumb,
        ]);
    }

    #[Route('/ajouter-un-nouveau-mot-rapide', name: 'app_mot_rapide_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MotRapideRepository $motRapideRepository, Breadcrumb $breadcrumb): Response
    {
        // Pilotage du fil d'ariane
        $breadcrumb->add("globals.app_name", null);
        $breadcrumb->add("account.index.title", null);
        $breadcrumb->add("mot_rapide.index.title", 'app_mot_rapide_index');
        $breadcrumb->add("mot_rapide.new.title", null);

        $motRapide = new MotRapide();
        $motRapide->setAccount($this->getUser());
        $form = $this->createForm(MotRapideType::class, $motRapide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motRapideRepository->save($motRapide, true);

            return $this->redirectToRoute('app_mot_rapide_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mot_rapide/new.html.twig', [
            'mot_rapide' => $motRapide,
            'form' => $form,
            'breadcrumb' => $breadcrumb
        ]);
    }

    #[Route('/{id}/mettre-a-jour', name: 'app_mot_rapide_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, MotRapideRepository $motRapideRepository): Response
    {
        $motRapide = $motRapideRepository->getMotRapide($id, $this->getUser());

        $form = $this->createForm(MotRapideType::class, $motRapide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motRapideRepository->save($motRapide, true);

            return $this->redirectToRoute('app_mot_rapide_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mot_rapide/edit.html.twig', [
            'mot_rapide' => $motRapide,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mot_rapide_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, MotRapideRepository $motRapideRepository): Response
    {
        $motRapide = $motRapideRepository->getMotRapide($id, $this->getUser());

        $motRapideRepository->remove($motRapide, true);

        return $this->redirectToRoute('app_mot_rapide_index', [], Response::HTTP_SEE_OTHER);
    }
}
