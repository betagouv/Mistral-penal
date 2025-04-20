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
use App\Service\Cassiopee\AudienceService;
use App\Service\Cassiopee\LoginService;
use App\Tests\KernelTestCase;

class AudienceServiceTest extends KernelTestCase {

  public function testRetrieveAudiences(): void {
      self::bootKernel();
      $container = static::getContainer();
      $loginService = $container->get(LoginService::class);
      $audienceService = $container->get(AudienceService::class);
      $login = "corinne.parent106";
      $password = "justice1";
      $this->section("Test sur le calendrier");
      $this
        ->given(
          description: "Je souhaite récupérer les informations d'audience",
          loginService: $loginService,
          audienceService: $audienceService,
          login: $login,
          password: $password
        )
        ->when(
          description: "Je consulte le planning de Janvier 2024",
          callback: function(
            LoginService $loginService,
            AudienceService $audienceService,
            string $login,
            string $password,
            ?array &$services=[],
            ?array &$audiences=[],
            ?string &$firstAudienceId=null
          ) {
              $loginInfos = $loginService->login($login, $password);
              if(!empty($loginInfos['cookies']))
                $audienceService->setCookies($loginInfos['cookies']);
              $services=$loginInfos['user']['services']??[];
              $audiences = $audienceService
                ->getAudiences(
                    \DateTime::createFromFormat("d-m-Y", "01-01-2024"),
                    $services
                )
              ;
              $firstAudienceId=$audiences[0]['id_cassiopee']??null;
          }
        )
        ->then(
          description: "Je dois avoir au moins une audience de trouvé",
          callback: function($firstAudienceId) {
            return (null!==$firstAudienceId);
          },
          result: true
        )
        ->andThen(
          description: "Je dois pouvoir retrouver les audiences pour un intervalle de date donné",
          callback: function(array $audiences,array $services) {
            /** @var bool $check */
            $check=false;
            foreach($audiences as $audience)
              $check=$check||(
                ($audience['heure_debut'] === '09:00')&&
                preg_match("/^09:00 - Chambre 15 - collégiale/",$audience['libelle_complet']) &&
                ($audience['type'] === 'collégiale')&&
                ($audience['libelle'] === 'Chambre 15')
              );
            return $check;
          },
          result: true
        )
        ->andWhen(
          description: "J'ai besoin des détails d'une audience",
          callback: function(AudienceService $audienceService, string $firstAudienceId, ?array &$audienceDetails=[]) {
            $audienceDetails = $audienceService
              ->getAudienceDetails($firstAudienceId)
            ;
          }
        )
        ->then(
          description: "Je dois retrouver le détail de ladite audience",
          callback: function(array $audienceDetails): bool {
            return (
                isset($audienceDetails['juridiction']) &&
                isset($audienceDetails['specialite']) &&
                isset($audienceDetails['affaires']) &&
                isset($audienceDetails['intervenants']) &&
                ($audienceDetails['juridiction']==='Tribunal Correctionnel de Grasse')
            );
          },
          result: true
        )
      ;
  }
}
