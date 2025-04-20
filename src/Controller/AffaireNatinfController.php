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

use App\Entity\AffaireNatinf;
use App\Form\AffaireType;
use App\Form\AffaireNatinf\AffaireNatinfUpdateType;
use App\Form\NoteAudience\NoteAudienceAddType;
use App\Repository\AffaireNatinfRepository;
use App\Repository\AffaireRepository;
use App\Service\Breadcrumb\Breadcrumb;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AffaireNatinfController extends AbstractController
{

    public function __construct(private AffaireNatinfRepository $affaireNatinfRepository) {}

    #[Route('/natinfs/{id}/publier', name: 'affaire_natinf_item_display', methods: ['GET'], options: ["expose" => true])]
    public function editGeneral(
        int $id,
        Request $request
    ): Response {
        $affaireNatinf = $this->affaireNatinfRepository->getAffaireNatinf($id, $this->getUser());

        return $this->render('affaire_personne/_natinf.html.twig', [
            'affaireNatinf' => $affaireNatinf,
        ]);
    }
}
