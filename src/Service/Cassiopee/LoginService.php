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
namespace App\Service\Cassiopee;

use App\Service\Cassiopee\AuthenticationException;
use App\Utils\Env;
use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use App\Service\Cassiopee\AbstractCassiopeeService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class LoginService extends AbstractCassiopeeService {

    const FOLLOW_REDIRECTS = false;

    use DebugTrait;

    private function authenticationRequest($browser, $username, $password) {
        $response = $browser->request(
          'POST',
          $this->url(Env::get("CASSIOPEE_CONNECT")),
          [
              'identifiantCassiopee' => $username,
              'password' => $password
          ]
        );
        return $response;
    }

    private function isAuthenticationSuccessful(HttpBrowser $browser) {
        $response = $browser->getInternalResponse();

        if ($response->getStatusCode() !== 302) {
            return false;
        }

        $location = $response->getHeader('Location');
        if (preg_match('/consulterPageAccueil\.do/', $location)) {
            return true;
        }

        if (preg_match('/listeServiceConnexion\.do/', $location)) {
            return true;
        }

        return false;
    }

    private function generateAuthenticationException(HttpBrowser $browser, Crawler $crawler) {
        $errorTitle = $crawler->filter('table.errorGeneral td.errorTitle');
        $errorMessage = $crawler->filter('table.errorGeneral a.messageImportant');

        if ($errorTitle->count() > 0 || $errorMessage->count() > 0) {
            $title = $errorTitle->count() > 0 ? $errorTitle->text() : "erreur";
            $message = $errorMessage->count() > 0 ? $errorMessage->text() : "";
            throw new AuthenticationException("$title : $message", 0);
        }

        $errors = $crawler->filter('td.errorBody i');
        if ($errors->count() > 0) {
            $title = $errors->text();
            throw new AuthenticationException($title);
        }

        $response = $browser->getInternalResponse();

        if ($response->getStatusCode() === 302) {
            $location = $response->getHeader("Location");
            if (preg_match("/messagesInformationPopup=([^&]*)/", $location, $matches)) {
                throw new AuthenticationException(urldecode($matches[1]));
            }
        }

        throw new AuthenticationException("Echec de l'authentification !");
    }

    private function getServices(HttpBrowser $browser, $serviceListUrl = null) {
        if ($serviceListUrl) {
            $crawler = $browser->request('GET', $serviceListUrl);
        } else {
            $crawler = $browser->request('GET', $this->url(Env::get('CASSIOPEE_USER_GET_SERVICES')));
        }

        $selectedService = null;

        $services = $this->browsePages(
            $browser,
            $crawler,
            "listServices",
            function($servicesCrawler) use (&$selectedService) {
                return $servicesCrawler->filter("#service tr")
                ->each(function($serviceNode) use (&$selectedService) {
                    $values = $serviceNode->filter('td');

                    if($values->count() < 3) return false;

                    $hrefNode = $values->eq(0)->filter('a')->first();
                    if ($hrefNode->count() <= 0) return false;


                    $hrefValue = $hrefNode->first()->attr('href');
                    $foundHref = preg_match ("/^javascript:submitForm\('\/(.*)&serviceId=([0-9,]+)'\);$/", $hrefValue, $matches);

                    if (!$foundHref) {
                        throw new AuthenticationException("Erreur interne - impossible de récupérer les identifiants des services !");
                    }

                    $href = $matches[1];
                    $serviceId = $matches[2];

                    $serviceData = [
                        "href" => $href,
                        "serviceId" => $serviceId,
                        "mnemo" => $values->eq(0)->text(),
                        "libelle" => $values->eq(1)->text(),
                        "remplacement" => $values->eq(2)->text() == 'oui'
                    ];

                    if($selectedService === null) {
                        $selectedService = $serviceData;
                    }

                    return $serviceData;
                });
            }
        );

        if ($selectedService === null) {
            throw new AuthenticationException("L'utilisateur n'a accès à aucun service !");
        }

        $form = $crawler->filter("form[name=listeServiceConnexionForm]")->form();

        $browser->request(
            'POST',
            $this->url($selectedService['href'] . "&serviceId=" . $selectedService['serviceId']),
            $form->getPhpValues()
        );

        return array_values(array_filter($services));
    }

    public function refresh(): bool {
      $browser = $this->getBrowser();
      $this->restoreCookies();
      if($browser) {
        $params = $this->getUserInfos($browser);
        return (count($params['services'])>0);
      }
      return false;
    }

    private function getUserInfos(HttpBrowser $browser) {
        //request user informations
        $crawler = $browser->request('GET', $this->url(Env::get('CASSIOPEE_USER_INFOS')));

        //extract user informations
        $params=[
            "identifiantUtilisateur" => null,
            'idCassiopee' => null,
            'civilite' => null,
            'nom' => null,
            'prenom' => null,
            'prenom2' => null,
            'email' => null,
            'mnemo' => null,
            'corps' => null,
            'fonction' => null,
            'titre' => null,
            'juridiction' => null
        ];


        foreach (array_keys($params) as $paramKey) {
            $nodes = $crawler->filter("input[name=$paramKey]");
            foreach ($nodes as $node) {
                $params[$paramKey] = $node->attributes->getNamedItem('value')->textContent;
            }
        }

        $params['services'] = $crawler
            ->filter('#tbl-container-kcop tbody:nth-child(2) tr')
            ->each(function ($tr) {
                $tds = $tr->filter('td');

                return [
                    "libelle" => $tds->eq(0)->text(),
                    "date_debut" =>  self::trim($tds->eq(1)->text()),
                    "date_fin" => self::trim($tds->eq(2)->text())
                ];
            });

        return $params;
    }

    public function login(string $username, string $password) {
        $browser = $this->getBrowser();

        //don"t follow redirects to avoid useless requests
        $browser->followRedirects(self::FOLLOW_REDIRECTS);

        //HEAD request to retrieve cassiopee cookies such as JSESSIONID
        $browser->request('GET', $this->url());


        //Try to connect to cassiopee using credentials
        $crawler = $this->authenticationRequest($browser, $username, $password);

        if (!$this->isAuthenticationSuccessful($browser)) {
            $this->generateAuthenticationException($browser, $crawler);
        }

        $serviceListUrl = $browser->getInternalResponse()->getHeader('Location');
        $serviceListUrl = preg_replace('/^http:\/\//', 'https://', $serviceListUrl);

        $services = $this->getServices($browser, $serviceListUrl);
        $params = $this->getUserInfos($browser);

        $obsoleteServiceIds = [];

        for ($i = 0; $i < count($services); $i++) {
            $service = $services[$i];

            $serviceDetails = null;

            foreach ($params["services"] as $s) {
                if ($s['libelle'] == $service['libelle']) {
                    $serviceDetails = $s;
                    break ;
                }
            }

            if ($serviceDetails != null) {
                if (!empty($serviceDetails["date_fin"])) {
                    $obsoleteServiceIds[] = $i;
                }

                $service["date_debut"] = $serviceDetails["date_debut"];
                $service["date_fin"] = $serviceDetails["date_fin"];
            } else {
                $service["date_debut"] = null;
                $service["date_fin"] = null;
            }

            $services[$i] = $service;
        }

        foreach($obsoleteServiceIds as $id) {
            unset($services[$i]);
        }

        $params["services"] = array_values($services);

        $cookiesData = $this->saveCookies();

        if ($cookiesData['jsessionid'] === null) {
            throw new AuthenticationException("le cookie de session cassiopée n'existes pas !", 500);
        }

        return [
            'user' => $params,
            'cookies' => $cookiesData['cookies'],
            'jsessionid' => $cookiesData['jsessionid']
        ];
    }

    public function getJuridictions() {
        $client = $this->createClientWithCookies();

        $response = $client->request('GET', $this->url(Env::get('CASSIOPEE_PLANNING_AUDIENCES')));

        $crawler = new Crawler(null, $response->getInfo('url'));
        $crawler->addContent($response->getContent());

        $juridictions = $crawler
            ->filter('select[name=idJuridiction] option')
            ->each(fn($option) => [
                "code" => $option->first()->attr('value'),
                "libelle" => $option->first()->text()
            ]);

        $responses = array_map(fn($juridiction) => $client->request(
            'GET',
            $this->url(
                Env::get('CASSIOPEE_PLANNING_AUDIENCES_JURIDICTIONS')
                . '&juridictionId=' . $juridiction['code']
            )
        ), $juridictions);

        foreach($client->stream($responses) as $response => $chunk) {
            if ($chunk->isLast()) {
                $responseCrawler = new Crawler(null, $response->getInfo('url'));
                $responseCrawler->addContent($response->getContent());

                $queryParams = [];
                parse_str(parse_url($response->getInfo('url'))["query"], $queryParams);
                $juridictionId = $queryParams["juridictionId"];

                $services = $responseCrawler
                    ->filter('select option')
                    ->each(function($service) {
                        $option = $service->first();
                        if (empty($option->attr("value"))) {
                            return null;
                        }

                        return [
                            "id_ksp" => $option->attr("value"),
                            "libelle" => $option->text()
                        ];
                    });

                $services = array_values(array_filter($services));

                for ($i = 0; $i < count($juridictions); $i++) {
                    if ($juridictions[$i]["code"] === $juridictionId) {
                        $juridictions[$i]["services"] = $services;
                        break ;
                    }
                }
            }
        }

        return $juridictions;
    }

}
