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

use App\Form\LoginType;
use App\Entity\Login;
use App\Service\Cassiopee\LoginService;
use App\Utils\Env;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private LoginService $loginService,
        private LoggerInterface $logger
    ) {}


    #[Route('/deconnexion', name: 'logout')]
    public function index(
      Request $request,
      AuthenticationUtils $authenticationUtils,
    ): Response
    {
      /**
       * @todo
       */
      return $this->redirectToRoute('login');
    }

    #[Route('/connexion', name: 'login')]
    public function connect(
      Request $request,
      AuthenticationUtils $authenticationUtils
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $login = new Login();
        $loginForm = $this->createForm(LoginType::class, $login);
        $loginForm->handleRequest($request);

        return $this->render('security/login.html.twig', [
          'last_username' => $lastUsername,
          'error' => $error,
          'login' => $login,
          'loginForm' => $loginForm->createView(),
        ]);
    }

    #[Route('/refresh', name: 'refresh_ksp', options: ["expose" => true])]
    public function refreshCassiopeeSession() {
        $isConnected = $this->loginService->refresh();

        return new JsonResponse(["isConnected" => $isConnected]);
    }
}
