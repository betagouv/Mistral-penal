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
namespace App\Controller\NoteAudience;

use App\Entity\Security\Account;
use App\Entity\Affaire;
use App\Repository\AffaireRepository;
use App\Repository\NoteAudienceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InsertOrUpdate extends AbstractController
{
    public function __construct(
        private AffaireRepository $affaireRepository,
        private NoteAudienceRepository $noteAudienceRepository
    ) {}


    #[Route('/affaires/{affaire_id}/note_audience', name: "affaire_note_audience_POST", methods: ['POST'], options: ["expose" => true])]
    public function insertOrUpdateNoteAudience(Request $request)
    {
        $account = $this->getUser();

        $content = json_decode($request->getContent(), true);
        $note = $content['note'] ?? $request->get('note', null);
        $affaireId = $content['affaire_id'] ?? $request->get('affaire_id', null);

        $affaire = $this->affaireRepository->getAffaire($affaireId, $account);

        if (null !== $affaire && null !== $account) {
            $noteAudience = $this->noteAudienceRepository->insertOrUpdate($affaire, $account, $note);
            return $this->json(["success" => true]);
        }

        return new JsonResponse(['errmsg' => 'Un problème est survenu!'], 404);
    }
}
