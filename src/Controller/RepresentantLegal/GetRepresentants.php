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
namespace App\Controller\RepresentantLegal;

use App\Entity\AffairePersonne;
use App\Entity\RepresentantLegal;
use App\Repository\AffairePersonneRepository;
use App\Repository\RepresentantLegalRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class GetRepresentants extends AbstractController
{

    private ?RepresentantLegalRepository $_rr = null;
    private ?AffairePersonneRepository $_apr = null;
    public function __construct(
        RepresentantLegalRepository $rr,
        AffairePersonneRepository $apr,
        private LoggerInterface $logger
    ) {
        $this->_rr = $rr;
        $this->_apr = $apr;
    }

    public function getAffairePersonneRepository(): ?AffairePersonneRepository
    {
        return $this->_apr;
    }

    public function getRepresentantLegalRepository(): ?RepresentantLegalRepository
    {
        return $this->_rr;
    }

    private static function formatOutput(array $representants): array
    {
        /** @var array $output */
        $output = [];
        foreach ($representants as $representant) {
            $lj = $representant->getLienJuridique();
            $ls = $representant->getLienSocial();
            $tmp = [
                'id' => $representant->getId(),
                'lien_juridique' => [
                    'id' => $lj ? $lj->getId() : null,
                    'code' => $lj ? $lj->getCode() : null,
                    'libelle' => $lj ? $lj->getLibelle() : null,
                ],
                'lien_social' => [
                    'id' => $ls ? $ls->getId() : null,
                    'code' => $ls ? $ls->getCode() : null,
                    'libelle' => $ls ? $ls->getLibelle() : null,
                ],
                'representant' => [
                    'personne' => [
                        'id' => $representant->getRepresentant()->getPersonne()->getId(),
                        'nom' => $representant->getRepresentant()->getPersonne()->getNom(),
                        'prenom1' => $representant->getRepresentant()->getPersonne()->getPrenom1(),
                        'nomComplet' => $representant->getRepresentant()->getPersonne()->getNomComplet()
                    ],
                    'affaire_personne' => [
                        'id' => $representant->getRepresentant()->getId(),
                    ]
                ],
            ];
            $output[] = $tmp;
        }
        return $output;
    }

    #[Route('/api/representants-pour-{id}', name: 'api_representant_legal_findall_GET_collection', methods: ['GET'], options: ["expose" => true])]
    public function __invoke(Request $request): JsonResponse
    {
        $rr = $this->getRepresentantLegalRepository();
        $apr = $this->getAffairePersonneRepository();

        /** @var array $content */
        $content = json_decode($request->getContent(), true);
        /** @var ?int $id */
        $id = $content['id'] ?? $request->get('id', null);
        $ap = $apr->getAffairePersonne($id, $this->getUser());
        $representants = $rr->findAllByRepresente($ap);

        return new JsonResponse(self::formatOutput($representants));
    }
}
