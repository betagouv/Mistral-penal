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
use App\Utils\Env;
use Symfony\Component\DomCrawler\Crawler;


class AudienceService extends AbstractCassiopeeService {

    public function getJuridiction() {
        $browser = $this->getBrowser();
        $crawler = $browser->request('POST', $this->url(Env::get('CASSIOPEE_PLANNING_AUDIENCES')));

        $juridictionNode = $crawler
            ->filter('select[name=idJuridiction] option')
            ->reduce(function($option) {
                return preg_match("/Tribunal Correctionnel/i", $option->text()) > 0;
            })
            ->first();

        return [
            "label" => $juridictionNode->text(),
            "value" => $juridictionNode->getNode(0)->attributes->getNamedItem("value")->textContent
        ];
    }

    private function parseAudience($audienceNode) {
        $audience = [];

        $audienceText = $audienceNode->text();
        $libelleStr = $audienceNode->filter('span.grasImportant')->text();
        $audienceText = str_replace($libelleStr, "", $audienceText);

        $audience["libelle"] = substr($this->trim($libelleStr), 0, -2);
        $audience["libelle_complet"] = $this->trim($audienceText);

        $fields = array_map(
            fn($f) => $this->trim($f),
            explode("-", $audienceText)
        );

        $audience["heure_debut"] = trim($fields[0]);
        $audience["type"] = trim($fields[1]);

        $timeFields = explode("/", $fields[2]);
        $audience["duree_evaluee"] = $timeFields[0];
        if (count($timeFields) >= 2) {
            $audience["duree_theorique"] =  $timeFields[1];
        } else {
            $audience["duree_theorique"] = "0h00";
        }

        if (count($fields) >= 4) {
            $quantiteFields = explode("/", $fields[3]);
            $audience["quantite_affaires"] = $quantiteFields[0];

            if (count($quantiteFields) >= 2) {
                $audience["quantite_affaires_max"] = $quantiteFields[1];
            } else {
                $audience["quantite_affaires_max"] = 0;
            }
        } else {
            $audience["quantite_affaires"] = 0;
            $audience["quantite_affaires_max"] = 0;
        }

        $href = $audienceNode->getNode(0)->attributes->getNamedItem("href")->textContent;
        preg_match("/audienceId=(?<audienceId>[0-9,]+)/", $href, $matches);
        $audience["id_cassiopee"] = $matches["audienceId"];

        return $audience;
    }

    private function fetchAudiences($browser, $startDate, $services, $juridictionId, $isWeekEnd = false, $audiences = null) {
        $crawler = $browser->request('POST', $this->url("agc/consulterPlanningAudience.do?reqCode=afficher"),[
            "dateDebut" => $startDate->format('dmY'),
            "servicesChecked" => join(';', array_map(fn($service) => $service['serviceId'], $services)),
            "idJuridiction" => $juridictionId,
            "nombreEchelle" => 4,
            "displayMode" => 2,
            "dateDebut_0" => $startDate->format('d'),
            "dateDebut_1" => $startDate->format('m'),
            "dateDebut_2" => $startDate->format('Y'),
            "codeEchelle" => "SEMAINE",
            "weekEnd" => $isWeekEnd ? "on" : "off"
        ]);

        $days = $crawler->filter("th.list");
        $daysAudiences = $crawler->filter("td.list");

        if (count($days) != count($daysAudiences)) {
            throw new \Exception("Impossible d'importer le calendrier - erreur de formattage", 501);
        }

        $daysCount = count($days);

        if ($audiences == null) {
            $audiences = [];
        }

        for ($i = 0; $i < $daysCount; $i++) {
            $day = $days->eq($i);
            $dayAudiences = $daysAudiences->eq($i);
            $date = null;

            if (preg_match("/([0-9]{2}\/[0-9]{2}\/[0-9]+)/", $day->text(), $matches)) {
                $date = \DateTime::createFromFormat("d/m/y", $matches[1]);
                $date->setTime(0,0,0);

                if (!$date) {
                    throw new \Exception("Impossible d'importer le calendrier - erreur de formattage", 502);
                }
            }

            $audienceNodes = $dayAudiences
                ->filter("a.lienList")
                ->each(function ($audienceNode) use (&$audiences, &$date) {
                    $audience = $this->parseAudience($audienceNode);
                    $audience["date"] = $date;
                    $audiences[] = $audience;
                });
        }

        return $audiences;
    }

    public function getAudiences(\DateTime $startDate, array $services) {
        $browser = $this->getBrowser();
        $browser->followRedirects(false);

        $juridiction = $this->getJuridiction($browser);

        $audiences = $this->fetchAudiences($browser, $startDate, $services, $juridiction["value"]);
        $audiences = $this->fetchAudiences($browser, $startDate, $services, $juridiction["value"], true, $audiences);

        return $audiences;
    }

    private function parseIntervenants($form) {
        $intervenants = [
            [
                'name' => 'composition.libellePresident',
                'id_ksp' => 'composition.idPresident',
                'role' => 'president',
            ],
            [
                'name' => 'composition.libelleAssesseur1',
                'id_ksp' => 'composition.idAssesseur1',
                'role' => 'assesseur1',
            ],
            [
                'name' => 'composition.libelleAssesseur2',
                'id_ksp' => 'composition.idAssesseur2',
                'role' => 'assesseur2',
            ],
            [
                'name' => 'composition.libelleAuditeur1',
                'id_ksp' => 'composition.idAuditeur1',
                'role' => 'auditeur',
            ],
            [
                'name' => 'composition.libelleAuditeur2',
                'id_ksp' => 'composition.idAuditeur2',
                'role' => 'auditeur',
            ],
            [
                'name' => 'composition.libelleMinistere',
                'id_ksp' => 'composition.idMinistere',
                'role' => 'ministere',
            ],
            [
                'name' => 'composition.libelleGreffier',
                'id_ksp' => 'composition.idGreffier',
                'role' => 'greffe',
            ],
            [
                'name' => 'composition.libelleGreffierStagiaire1',
                'id_ksp' => 'composition.idGreffierStagiaire1',
                'role' => 'greffe_stagiaire',
            ]
        ];

        return array_map(function ($intervenant) use (&$form) {
            return [
                "role" => $intervenant["role"],
                "libelle_complet" => $form[$intervenant["name"]]->getValue(),
                "id_ksp" => $form[$intervenant["id_ksp"]]->getValue()
            ];
        }, $intervenants);
    }

    private function parseAffaires(Crawler $crawler) {
        $affaires = $crawler
            ->filter("#listAffairesFixees")
            ->filter('#tbl-container-kcop')
            ->filter('tr.odd,tr.even')
            ->each(function ($affaireNode) {
                $affaire = [];
                $fields = $affaireNode->filter('td.list');

                if(count($fields) < 10) {
                    return null;
                }

                $link = $fields->filter("a")->getNode(0)->getAttribute("href");

                $affaire["id_ksp_affaire"] = null;
                if(preg_match("/identifierAffaire[=](?<id_ksp_affaire>[^&]+)(&|$)/i", $link, $matches)) {
                    $affaire["id_ksp_affaire"] = $matches['id_ksp_affaire'];
                }

                $affaire["id_ksp_parquet"] = null;
                if(preg_match("/identifierIdenParquet[=](?<id_ksp_parquet>[^&]+)(&|$)/i", $link, $matches)) {
                    $affaire["id_ksp_parquet"] = $matches['id_ksp_parquet'];
                }

                $affaire["type"] = null;
                if(preg_match("/typeAffaire[=](?<type_affaire>[^&]+)(&|$)/i", $link, $matches)) {
                    $affaire["type"] = $matches['type_affaire'];
                }

                $offset = count($fields) === 11 ? 1 : 0;

                $affaire["numero_parquet"] = $fields->eq(0 + $offset)->text();
                $affaire["numero_cabinet"] = $fields->eq(1 + $offset)->text();
                $affaire["nature_procedure"] = $fields->eq(3 + $offset)->text();
                $affaire["nombre_prevenu_convoque"] = $fields->eq(7 + $offset)->text();
                $affaire["nombre_detenu_convoque"] = $fields->eq(8 + $offset)->text();
                $affaire["duree"] = $affaireNode->filter('input[name=duree]')->getNode(0)->getAttribute('value');

                return $affaire;
            });

        $affaires = array_values(array_filter($affaires));

        return $affaires;
    }


    public function getAudienceDetails(string $audienceId) {
        $browser = $this->getBrowser();
        $browser->followRedirects(false);

        $crawler = $browser->request("GET", $this->url(Env::get("CASSIOPEE_AUDIENCE") . "&audienceId=" . $audienceId));

        $form = $crawler->filter("form[name=consulterAudienceForm]")->form();

        $mapping = [
            "id_cassiopee" => "audienceId",
            "specialite" => "detailAudience\\.libelleSpecialite",
            "juridiction" => "detailAudience\\.libelleJuridiction",
            "date" => "detailAudience\\.date",
            "libelleService" => "detailAudience\\.libelleChambre",
            "heure_debut" => "detailAudience\\.heureDebut",
            "duree_theorique" => "detailAudience\\.affichageDureeReservee",
            "quantite_affaires" => "detailAudience\\.remplissage"
        ];

        $audience = self::mapForm($crawler, $mapping);

        $audience["heure_debut"] = substr($audience["heure_debut"], 0, 2) . ":" . substr($audience["heure_debut"], 2);

        $audience["quantite_affaires"] = explode("/", $audience["quantite_affaires"])[1];
        $audience["duree_evaluee"] = null;
        $audience["date"] = \DateTime::createFromFormat(
            "dmY",
            $audience["date"]
        );

        $audience["intervenants"] = $this->parseIntervenants($form);

        $audience["affaires"] = $this->browsePages(
            $browser,
            $crawler,
            "listAffairesFixees",
            fn($paginationCrawler) => $this->parseAffaires($paginationCrawler)
        );

        return $audience;
    }
}
