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
namespace App\Test\Service\Cassiopee;
use App\Service\Cassiopee\AuthenticationException;
use App\Service\Cassiopee\LoginService;
use App\Tests\KernelTestCase;

class LoginServiceTest extends KernelTestCase {

  public function testLogin(): void {
      self::bootKernel();
      $container = static::getContainer();
      $loginService = $container->get(LoginService::class);
      $login = "corinne.parent106";
      $password = "justice1";
      $this->section("Test sur la connexion");
      $this
        ->given(
          description: "Je souhaite tester la connexion à Cassiopée",
          loginService: $loginService,
          login: $login,
          password: $password
        )
        ->when(
          description: "Je saisis un mauvais login / mot de passe",
          callback: function(LoginService $loginService,?AuthenticationException &$exception=null) {
            try {
              $loginService->login("wrong.username", "wrong.password");
            }
            catch(AuthenticationException $e) {
              $exception = $e;
            }
            catch(\Exception $e) {
            }
          }
        )
        ->then(
          description: "Je lève une exception de mauvaise connexion",
          callback: function($exception) {
            return (null !== $exception);
          },
          result: true
        )
        ->when(
          description: "J'utilise un login / mot de passe valide",
          callback: function($loginService, $login, $password, ?array &$loginInfos=[], ?\Exception &$exception=null) {
            $exception = null;
            try {
              $loginInfos = $loginService->login($login, $password);
            }
            catch(\Exception $e) {
              $exception = $e;
            }
          }
        )
        ->then(
          description: "Je ne lève aucune erreur",
          callback: function($exception): bool {
            return (null === $exception);
          },
          result: true
        )
        ->andThen(
          description: "Je retrouve les sous-éléments de la juridiction associée",
          callback: function($loginService): bool {
            $items = $loginService->getJuridictions();
            $check = false;
            foreach($items as $item)
              $check=$check||(
                ($item['code']==='1-13214,0') &&
                ($item['libelle']==='Président du tribunal judiciaire de Grasse')
              );
            return $check;
          },
          result: true
        )
        ->andThen(
          description: "Je récupère les informations structurantes de session, d'utilisateur et des services associés",
          callback: function($loginInfos): bool {
            return
              !empty($loginInfos['jsessionid']) &&
              !empty($loginInfos['user']) &&
              !empty($loginInfos['user']['services']) &&
              !empty($loginInfos['user']['juridiction'])
            ;
          },
          result: true
        )
        ->andThen(
          description: "Je retrouve les informations complètes de corinne.parent106",
          callback: function($loginInfos): bool {
            $user = $loginInfos["user"];
            return
              !empty($user['idCassiopee']) &&
              ($user['idCassiopee'] === 'corinne.parent106') &&
              !empty($user["mnemo"]) &&
              ($user["mnemo"] === 'GREF15') &&
              !empty($user["juridiction"]) &&
              ($user["juridiction"] === 'Tribunal judiciaire de Grasse')
            ;
          },
          result: true
        )
        ->andThen(
          description: "Je retrouve bien les services rattachés",
          callback: function($loginInfos): bool {
            /** @var array $services */
            $services = !empty($loginInfos["user"]["services"]) ? $loginInfos["user"]["services"] : [];
            /** @var bool $check */
            $check = false;
            foreach($services as $service) {
              $check=$check||(
                ($service['mnemo'] === 'TCCH15') &&
                ($service['remplacement'] === false) &&
                ($service['libelle'] === 'TC - Chambre 15') &&
                ($service['href'] === "aut/listeServiceConnexion.do?reqCode=valider") &&
                ($service['serviceId'] === '100048103915,0,0') &&
                ($service['date_debut'] === '2005-01-01 00:00:00.0')
              );
            }
            return $check;
          },
          result: true
        )
      ;
  }
}
