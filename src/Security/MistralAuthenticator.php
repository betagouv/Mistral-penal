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
namespace App\Security;

use App\Repository\Security\AccountRepository;
use App\Repository\Security\UtilisateurAccrediteRepository;
use App\Service\Cassiopee\AuthenticationException;
use App\Service\Cassiopee\LoginService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class MistralAuthenticator extends AbstractLoginFormAuthenticator {
    use TargetPathTrait;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator, 
        private AccountRepository $accountRepository,
        private UtilisateurAccrediteRepository $utilisateurAccrediteRepository,
        private LoginService $loginService) {}


    protected function getLoginUrl(Request $request): string {
        return $this->urlGenerator->generate("login");
    }

    function authenticate(Request $request): Passport {

        $login = $request->request->all('login');

        if (!is_array($login) 
            || !array_key_exists("username", $login) 
            || !array_key_exists("password", $login)) {

                throw new CustomUserMessageAuthenticationException("Requête invalide !");
        }

        $username = $login["username"];
        $password = $login["password"];

        $account = $this->accountRepository->findOneByUsername($username);

        if ($account && $account->isAdminFonc()) {
            return new Passport(new UserBadge($username), new PasswordCredentials($password));
        } else {

            $utilisateurAccredite = $this->utilisateurAccrediteRepository->findOneByUsername($username);

            if (!$utilisateurAccredite) {
                throw new CustomUserMessageAuthenticationException(("L'utilisateur n'est pas accrédité !"));
            }

            try {
                $loginInfos = $this->loginService->login($username, $password);
            } catch (AuthenticationException $e) {
                $message = $e->getMessage();
                if ($e->getCode() >= 500) {
                    $message = "Erreur interne";
                }

                throw new CustomUserMessageAuthenticationException($message);
            }

            $account = $this
                ->accountRepository
                ->insertOrUpdate(
                    $loginInfos['user'], 
                    fn() => $this->loginService->getJuridictions()
                );
            
            return new SelfValidatingPassport(new UserBadge($username));
        }
    }

    function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response|null {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate("homepage"));
    }

}