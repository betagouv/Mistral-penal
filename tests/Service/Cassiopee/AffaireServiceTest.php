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
use App\Service\Cassiopee\AffaireService;
use App\Service\Cassiopee\LoginService;
use App\Tests\KernelTestCase;

class AffaireServiceTest extends KernelTestCase
{

    public function testRetrieveAudiences(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $loginService = $container->get(LoginService::class);
        $audienceService = $container->get(AudienceService::class);
        $affaireService = $container->get(AffaireService::class);
        $login = "corinne.parent106";
        $password = "justice1";
        $this->section("Test sur une audience");
        $this
            ->given(
                description: "Je souhaite récupérer les informations d'audience",
                loginService: $loginService,
                audienceService: $audienceService,
                affaireService: $affaireService,
                login: $login,
                password: $password
            )
            ->when(
                description: "Je choisis une affaire du planning de Janvier 2024 ayant au moins un prévenu",
                callback: function (LoginService $loginService, AudienceService $audienceService, AffaireService $affaireService, string $login, string $password, ?array &$affaire = []) {
                    $loginInfos = $loginService->login($login, $password);
                    if (!empty($loginInfos['cookies'])) {
                        $audienceService->setCookies($loginInfos['cookies']);
                        $affaireService->setCookies($loginInfos['cookies']);
                    }
                    $services = $loginInfos['user']['services'] ?? [];
                    $audiences = $audienceService
                        ->getAudiences(
                            \DateTime::createFromFormat("d-m-Y", "01-01-2024"),
                            $services
                        )
                    ;
                    $infoAffaire = null;
                    foreach ($audiences as $audience) {
                        if ($audience['quantite_affaires'] > 0) {
                            $infoAffaires = $audienceService->getAudienceDetails($audience['id_cassiopee']);
                            $cAffaire = $infoAffaires['affaires'][0];
                            if ($cAffaire['nombre_prevenu_convoque'] > 0) {
                                $infoAffaire = $cAffaire;
                                break;
                            }
                        }
                    }
                    if (null === $infoAffaire)
                        throw new \Exception("test impossible à réaliser - aucune affaire trouvée pour tester");

                    $affaire = $this->__runFiber(
                        fn() => $affaireService->getAffaireDetails(
                            affaireId: $infoAffaire['id_ksp_affaire'],
                            parquetId: $infoAffaire['id_ksp_parquet'],
                            type: $infoAffaire['type'],
                            numeroParquet: $infoAffaire['numero_parquet']
                        )
                    );
                }
            )
            ->then(
                description: "Je dois retrouver les informations de l'affaire",
                callback: function ($affaire) {
                    return (
                        !empty($affaire['identifiant_justice']) &&
                        !empty($affaire['type_infraction']) &&
                        !empty($affaire['acte_saisine']) &&
                        isset($affaire['personnes']) &&
                        count($affaire['personnes'])
                    );
                },
                result: true
            )
        ;
    }

    public function testPersonnesLieesKspBug()
    {
        self::bootKernel();
        $container = static::getContainer();
        $loginService = $container->get(LoginService::class);
        $audienceService = $container->get(AudienceService::class);
        $affaireService = $container->get(AffaireService::class);
        $login = "corinne.parent106";
        $password = "justice1";

        $loginInfos = $loginService->login($login, $password);
        $affaireService->setCookies($loginInfos['cookies']);

        $affaire = $this->__runFiber(
            fn() => $affaireService->getAffaireDetails(
                affaireId: "1000000001780,0,0",
                parquetId: "1000000001780,0,0",
                type: "PENALE_GENERALE",
                numeroParquet: "20275000001"
            )
        );

        fwrite(STDERR, print_r($affaire, true));

        $this->assertEquals(count($affaire["personnes"][0]["personnes_liees"]), 1);
    }

    public function testAffaireEtatCivil()
    {
        self::bootKernel();
        $container = static::getContainer();
        $loginService = $container->get(LoginService::class);
        $audienceService = $container->get(AudienceService::class);
        $affaireService = $container->get(AffaireService::class);
        $login = "corinne.parent106";
        $password = "justice1";

        $loginInfos = $loginService->login($login, $password);
        $affaireService->setCookies($loginInfos['cookies']);

        $affaire = $this->__runFiber(
            fn() => $affaireService->getAffaireDetails(
                affaireId: "1000000010128,0,0",
                parquetId: "1000000010252,0,0",
                type: "PENALE",
                numeroParquet: "24250000014"
            )
        );

        fwrite(STDERR, print_r($affaire, true));

        $this->assertEquals("Lionel", $affaire["personnes"][0]["pere"]["prenoms"]);
        $this->assertEquals("729 Boulevard de la frite", $affaire["personnes"][0]["adresse"]["ligne_1"]);
    }

    public function testAffairePenaleRequete()
    {
        self::bootKernel();
        $container = static::getContainer();
        $loginService = $container->get(LoginService::class);
        $audienceService = $container->get(AudienceService::class);
        $affaireService = $container->get(AffaireService::class);
        $login = "corinne.parent106";
        $password = "justice1";

        $loginInfos = $loginService->login($login, $password);
        $affaireService->setCookies($loginInfos['cookies']);

        $affaire = $this->__runFiber(
            fn() => $affaireService->getAffaireDetails(
                affaireId: "1000000009509,0,0",
                parquetId: "1000000009610,0,0",
                type: "PENALE_REQ",
                numeroParquet: "24226000003"
            )
        );

        $this->assertEquals("PENALE_REQUETE", $affaire["type"]);
    }
    public function testIgnorePersonneEtatRecours()
    {
        self::bootKernel();
        $container = static::getContainer();
        $loginService = $container->get(LoginService::class);
        $audienceService = $container->get(AudienceService::class);
        $affaireService = $container->get(AffaireService::class);
        $login = "corinne.parent106";
        $password = "justice1";

        $loginInfos = $loginService->login($login, $password);
        $affaireService->setCookies($loginInfos['cookies']);

        $affaire = $this->__runFiber(
            fn() => $affaireService->getAffaireDetails(
                affaireId: "1000000009247,0,6",
                parquetId: "1000000009348,0,0",
                type: "PENALE_GENERALE",
                numeroParquet: "24205000003"
            )
        );

        $this->assertEquals("Jugé", $affaire["personnes"][0]["role"]);
        $this->assertEquals("Partie civile", $affaire["personnes"][1]["role"]);
    }

    private function __runFiber($func)
    {
        $result = null;
        $fiber = new \Fiber($func);
        $fiber->start();

        while ($fiber) {
            if ($fiber->isSuspended()) {
                $fiber->resume();
            } else if ($fiber->isTerminated()) {
                $result = $fiber->getReturn();
                $fiber = null;
            }
        }

        return $result;
    }
}
