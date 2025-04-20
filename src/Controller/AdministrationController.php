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

use App\Entity\Security\UtilisateurAccredite;
use App\Form\Account\AccountChangePasswordType;
use App\Form\DonneesGeneralesType;
use App\Repository\Security\AccountPasswordMemoryRepository;
use App\Repository\Security\AccountRepository;
use App\Repository\Security\DonneesGeneralesRepository;
use App\Repository\Security\UtilisateurAccrediteRepository;
use App\Service\Breadcrumb\Breadcrumb;
use App\Service\SysLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdministrationController extends AbstractController
{
    public function __construct(private UtilisateurAccrediteRepository $utilisateurAccrediteRepository)
    {
    }

    #[IsGranted('ROLE_ADMIN_FONC')]
    #[Route('/administration/suppression-habilitation/{id}', name: 'admin_func_delete_habilitation', methods: ['GET'])]
    public function adminSuppressionHabilitation(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        SysLog $syslog
    ) {
        $utilisateurAccredite = $this->utilisateurAccrediteRepository->findOneBy(["id" => $id]);

        $user = $this->getUser();
        $dg = $utilisateurAccredite->getDonneesGenerales();
        $dg->removeUtilisateursAccredite($utilisateurAccredite);
        $em->remove($utilisateurAccredite);
        $em->flush();
        $syslog
            ->setAction(SysLog::ADM_DEL_USER)
            ->append('from', $user->getUsername())
            ->append('to', $utilisateurAccredite->getUsername())
            ->flush()
        ;
        return new JsonResponse('Utilisateur supprimé');
    }

    #[IsGranted('ROLE_ADMIN_FONC')]
    #[Route('/administration/changement-mdp', name: 'admin_func_change_mdp', methods: ['POST', 'GET'])]
    public function adminChangePassword(
        Request $request,
        Breadcrumb $breadcrumb,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        AccountPasswordMemoryRepository $apmr,
        TranslatorInterface $trans
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(AccountChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            $check = true;
            if (!preg_match("/[^a-zA-Z0-9]/", $password)) {
                $msg = $trans->trans('instance_of_service.admin_func_change_mdp.err_on_special_char');
                $form->get('password')->addError(new FormError($msg));
                $check = false;
            }
            if (!preg_match("/[0-9]/", $password)) {
                $msg = $trans->trans('instance_of_service.admin_func_change_mdp.err_on_numeric');
                $form->get('password')->addError(new FormError($msg));
                $check = false;
            }
            if ($passwordHasher->isPasswordValid($user, $password)) {
                $msg = $trans->trans('instance_of_service.admin_func_change_mdp.invalid_password');
                $form->get('password')->addError(new FormError($msg));
                $check = false;
            }
            if ($apmr->isPasswordAllreadyUsed($passwordHasher, $user, $password)) {
                $msg = $trans->trans('instance_of_service.admin_func_change_mdp.password_allready_used');
                $form->get('password')->addError(new FormError($msg));
                $check = false;
            }

            # validation du mot de passe
            if (true === $check) {
                $apmr->freeze($user);
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $password)
                );
                $user->setDateChangementMDP(new \DateTime());
                $em->flush();
                return $this->redirectToRoute('admin_func');
            }

        }
        return $this->render('audience/admin_func_change_mdp.html.twig', [
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => 'instance_of_service.admin_func_change_mdp.title',
        ]);
    }

    #[IsGranted('ROLE_ADMIN_FONC')]
    #[Route('/administration', name: 'admin_func')]
    public function adminIndex(
        Request $request,
        EntityManagerInterface $em,
        AccountRepository $ar,
        Breadcrumb $breadcrumb,
        DonneesGeneralesRepository $dr,
        SysLog $syslog
    ): Response {
        $user = $this->getUser();
        /** @var DonneesGenerales $donneesGenerales */
        $donneesGenerales = $dr->get();
        $form = $this->createForm(DonneesGeneralesType::class, $donneesGenerales);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('utilisateurAccredite')->getData();
            if (!empty($username)) {
                $syslog->setAction(SysLog::ADM_ADD_USER);
                $syslog
                    ->append('from', $user->getUsername())
                    ->append('to', $username)
                ;
                $uar = $em->getRepository(UtilisateurAccredite::class);
                if (!$uar->findOneByUsername($username)) {
                    $ua = new UtilisateurAccredite();
                    $ua->setUsername($username);
                    $em->persist($ua);
                    $donneesGenerales->addUtilisateursAccredite($ua);
                    $em->flush();
                    $syslog->flush();
                }
            }
        }
        $user = $this->getUser();
        if (true === $ar->hasPasswordExpired($user))
            return $this->redirectToRoute('admin_func_change_mdp');

        return $this->render('audience/admin_func.html.twig', [
            'breadcrumb' => $breadcrumb,
            'title' => 'instance_of_service.admin_func.title',
            'form' => $form->createView(),
            'donneesGenerales' => $donneesGenerales,
        ]);
    }
}