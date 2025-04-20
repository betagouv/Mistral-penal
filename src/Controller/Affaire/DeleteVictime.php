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
namespace App\Controller\Affaire;

use App\Entity\Affaire;
use App\Entity\AffairePersonne;
use App\Repository\AffairePersonneRepository;
use App\Repository\AffaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class DeleteVictime extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AffaireRepository $affaireRepository,
        private AffairePersonneRepository $affairePersonneRepository
    ) {}

    #[Route('/api/affaires/{affaire_id}/victimes/{victime_id}', name: "api_affaire_victime_DELETE", methods: ['DELETE'], options: ["expose" => true])]
    public function deleteVictime(Request $request) {
        $affaireId = $request->get("affaire_id");
        $victimeId = $request->get("victime_id");

        if ($affaireId == null || $victimeId == null) {
            throw new BadRequestException("missing affaire id or victime id");            
        }

        $affaire = $this->affaireRepository->getAffaire($affaireId, $this->getUser());
        $victime = $this->affairePersonneRepository->findOneBy(["id" => $victimeId, "affaire" => $affaireId]);
        
        if (!$affaire || !$victime) {
            throw new NotFoundHttpException();
        }

        try {
            $representants = $victime->getRepresentants();
            foreach ($representants as $representant)
                $this->em->remove($representant);
            $affaire->removeAffairePersonne($victime);
            $this->em->remove($victime);
            $this->em->flush();
        } catch (\Exception $e) {
            //@todo log error
            return new JsonResponse(['message' => 'erreur lors de la suppression de la victime'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['message' => 'victime supprimée']);
    }
}
