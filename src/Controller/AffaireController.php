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
use App\Form\AffaireType;
use App\Form\AffaireNatinf\AffaireNatinfRequalDisqualType;
use App\Form\DecisionType;
use App\Form\NoteAudience\NoteAudienceAddType;
use App\Form\RenvoiType;
use App\Repository\AffaireRepository;
use App\Service\Breadcrumb\Breadcrumb;
use App\Service\NatinfsHelper;
use Doctrine\ORM\EntityManagerInterface;
use App\Utils\Env;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;

class AffaireController extends AbstractController
{
    public function __construct(private LoggerInterface $logger, private Security $security, private AffaireRepository $affaireRepository)
    {
    }

    #[Route('/mon-affaire/{affaireId}/general', name: 'affaire_edit_general', methods: ['GET', 'POST'], options: ["expose" => true])]
    public function editGeneral(
        int $affaireId,
        Request $request,
        Breadcrumb $breadcrumb,
        EntityManagerInterface $em,
        AffaireRepository $affaireRepository
    ): Response {
        $user = $this->security->getUser();

        ["affaire" => $affaire, "audience" => $audienceRelated] = $affaireRepository->getFullAffaire($affaireId, $user);

        /** @var bool $forceOutputJSON */
        $forceOutputJSON = (bool) $request->get('FORCE_OUTPUT_JSON', false);
        $audience = $affaire->getAudience();
        //$audience->setDateMiseAJour(new \DateTime());
        //$em->flush();

        // Pilotage du fil d'ariane
        $breadcrumb->add("globals.app_name", null);
        $breadcrumb->add("instance_of_service.index.title", 'audience_list');
        $breadcrumb->add("instance_of_service.show.title", 'audience_show', ['id' => $affaire->getAudience()->getId()]);
        $breadcrumb->add("affaire.edit.title", null, [], ['%identifiant_justice%' => $affaire->getNumeroDossier()]);
        $form = $this->createForm(AffaireType::class, $affaire, ['affaire' => $affaire]);
        $form->handleRequest($request);

        /** @var NoteAudience $noteAudience */
        $noteAudience = $affaire->getLastNoteAudience();
        /** @var Form $formNoteAudience */
        $formNoteAudience = $this->createForm(NoteAudienceAddType::class, $noteAudience, [
            'entity_manager' => $em,
            'affaire' => $affaire,
            'user' => $this->getUser(),
        ]);
        $formNoteAudience->handleRequest($request);

        $formNatinfRequalDisqual = $this->createForm(AffaireNatinfRequalDisqualType::class);
        $formNatinfRequalDisqual->handleRequest($request);

        $formRenvoi = $this->createForm(RenvoiType::class, null, ['affaire' => $affaire]);
        $formRenvoi->handleRequest($request);

        if ($formRenvoi->isSubmitted() && $formRenvoi->isValid()) {

            $renvoi = $formRenvoi->getData();
            $em->persist($renvoi);
            $em->flush();
        }

        $formDecision = $this->createForm(DecisionType::class, null, [
            'affaire' => $affaire,
            'affaireNatinfsFromAffaire' => true
        ]);

        $errors = '';
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
        } else {
            foreach ($form->getErrors(true) as $error) {
                $errors .= (implode(', ', $error->getMessageParameters()));
                $errors .= " " . $error->getMessage();
                $errors .= " //  ";
            }
        }

        // On génère une clé pour le cryptage local
        $localKey = $this->getUser()->getLocalKey();
        if ($localKey == null) {
            $localKey = bin2hex(openssl_random_pseudo_bytes(16));
            $this->getUser()->setLocalKey($localKey);
            $em->flush();
        }

        //outdated
        $outdated = new \DateTime();
        $outdated->modify(sprintf('- %d hours', Env::get('AUDIENCE_OUTDATED_DELAI')));

        $session = $request->getSession();
        $session->set('localKey', $localKey);

        if ((true === $request->isXmlHttpRequest()) || (true === $forceOutputJSON)) {
            return new JsonResponse(['success' => true]);
        }

        return $this->render('audience/show.html.twig', [
            'audience' => $affaire->getAudience(),
            'breadcrumb' => $breadcrumb,
            'affaire' => $affaire,
            'audienceRelated' => $audienceRelated,
            'form' => $form->createView(),
            'formNoteAudience' => $formNoteAudience->createView(),
            'formNatinfRequalDisqual' => $formNatinfRequalDisqual->createView(),
            'formRenvoi' => $formRenvoi->createView(),
            'formDecision' => $formDecision->createView(),
            'errors' => $errors,
            'outdated' => $outdated,
        ]);
    }

    #[Route('/mon-affaire/{id}/ma-note-d-audience', name: 'affaire_edit_note_audience', methods: ['GET', 'POST'], options: ["expose" => true])]
    public function showNoteAudience(int $id, Request $request, Breadcrumb $breadcrumb, EntityManagerInterface $em): Response
    {
        /** @var AffaireRepository $repo */
        $repo = $em->getRepository(Affaire::class);

        $affaire = $repo->getAffaire($id, $this->getUser());

        $repo->initIntervenants($affaire);
        /** @var NoteAudience $noteAudience */
        $noteAudience = $affaire->getLastNoteAudience();
        /** @var Form $form */
        $form = $this->createForm(NoteAudienceAddType::class, $noteAudience, [
            'entity_manager' => $em,
            'affaire' => $affaire,
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        return $this->render('audience/show_note_audience.html.twig', [
            'affaire' => $affaire,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mon-affaire/{id}/decision/affaire-natinfs', name: 'affaire_decision_affaire_natinfs', methods: ['GET'], options: ["expose" => true])]
    public function getAffaireNatinfs(int $id, NatinfsHelper $natinfsHelper): Response
    {
        $affaire = $this->affaireRepository->getAffaire($id, $this->getUser());

        return new JsonResponse(
            [
                'codes' => $natinfsHelper->getNatinfsCodes($affaire),
                'affairePersonnes' => $natinfsHelper->getNatinfsForPersonne($affaire),
            ]
        );
    }
}
