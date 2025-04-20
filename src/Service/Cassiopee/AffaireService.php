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

use App\Entity\ModaliteParticipation;
use App\Utils\Env;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class AffaireService extends AbstractCassiopeeService
{
    private function parseAffaireDetails($crawler)
    {
        $mapping = [
            "id_ksp_affaire" => "identifierAffaire",
            "type" => "typeAffaire",
            "identifiant_justice" => ["identifiantJustice", "blocSaisineForm\\.identifiantJustice"],
            "parquetier_en_charge" => "mnemoParquetierEnCharge",
            "service_en_charge" => "mnemoServiceParquetierEncharge",
            "type_infraction" => "typeInfraction",
            "emetteur" => "blocSaisineForm\\.emetteur\\.libelle",
            "acte_saisine" => "blocSaisineForm\\.acteSaisineLibelle",
            "date_saisine" => "blocSaisineForm\\.date",
            "nombre_vehicule" => "nbVehicules",
            "scelle" => "scelle",
            "scelle_agrasc" => "indicateurScelleAGRASC",
            "origine" => "origineAffaire",
        ];

        $affaire = self::mapForm($crawler, $mapping);

        $affaire["scelle"] = $affaire["scelle"] === "Oui";
        $affaire["scelle_agrasc"] = $affaire["scelle_agrasc"] === "Oui";
        $affaire["date_saisine"] = preg_replace(
            "/(\d{2})(\d{2})(\d{4})/",
            "$3-$2-$1",
            $affaire["date_saisine"]
        );

        $data = [
            "affaire_jirs" => "affaireJirs",
            "plainte_en_ligne" => "ppelDemandee",
            "acces_pnat" => "accesAffairePNAT",
            "acces_pnf" => "accesAffairePNF",
            "pole_instruction" => "poleInstruction",
            "affaire_eurojust" => "affaireEurojust",
        ];

        $form = $crawler->form();

        foreach ($data as $key => $selector) {
            if ($form->has($selector)) {
                $affaire[$key] = !$form[$selector]->isDisabled();
            }
        }

        return $affaire;
    }

    private function parseNataffs($form)
    {
        $i = 1;
        $nataffs = [];
        while (true) {
            $selector = $i > 1 ? $i : "";
            $codeNataffFieldName = "codeNataff" . $selector;
            $libelleNataffFieldName = "libelleNataff" . $selector;

            if (
                !$form->has($codeNataffFieldName) ||
                !$form->has($libelleNataffFieldName)
            ) {
                break;
            }

            $code = $form[$codeNataffFieldName]->getValue();
            $libelle = $form[$libelleNataffFieldName]->getValue();

            if (empty($code) || empty($libelle)) {
                break;
            }

            $nataffs[] = [
                "code" => $code,
                "libelle" => $libelle,
            ];

            $i++;
        }

        return $nataffs;
    }

    private function parseNatinfs($crawler)
    {
        $natinfs = [];

        $crawler
            ->filter("#listInfraction #item")
            ->filter("tr.odd,tr.even")
            ->each(function ($natinf) use (&$natinfs) {
                $natinfFields = $natinf->filter("td.list");

                if (
                    !count($natinfFields) ||
                    empty($natinfFields->eq(0)->text())
                ) {
                    return;
                }

                $link = $natinfFields
                    ->filter("a")
                    ->getNode(0)
                    ->getAttribute("href");

                if (
                    preg_match(
                        "/identifier[=](?<id_ksp_natinf>[^&]+)(&|$)/i",
                        $link,
                        $matches
                    )
                ) {
                    $idKspNatinf = $matches["id_ksp_natinf"];

                    $natinf = [
                        "id_ksp" => $idKspNatinf,
                        "link" => preg_replace(
                            "/^javascript:submitFormNoValidation\('(.*)'\);$/",
                            '$1',
                            $link
                        ),
                    ];

                    $natinfs[] = $natinf;
                }
            });

        return $natinfs;
    }

    private function parsePersonnes(Crawler $crawler)
    {
        $personnes = [];

        $crawler
            ->filter("#personnesBean")
            ->filter("tr.odd,tr.even")
            ->each(function ($personne) use (&$personnes, &$currentPersonne) {
                $personneFields = $personne->filter(
                    "td.list"
                );

                if (count($personneFields) !== 9) {
                    return;
                }

                $linkField = $personneFields->eq(1)->filter("a");
                if (count($linkField) <= 0) {
                    $linkField = $personneFields->eq(2)->filter("a");
                }

                if (count($linkField) > 0) {
                    $link = preg_replace(
                        "/^javascript:submitFormNoValidation\('\/(.*)'\);$/",
                        "$1",
                        $linkField->getNode(0)->getAttribute("href")
                    );
                } else {
                    $link = null;
                }

                $codeStatut = null;
                if (
                    preg_match(
                        "/rolePersonne[=](?<code_statut>[^&']+)(&|$|')/",
                        $link,
                        $matches
                    )
                ) {
                    $codeStatut = $matches["code_statut"];
                }

                $idKspPersonne = null;
                if (
                    preg_match(
                        "/identifierPersonne[=](?<id_ksp_personne>[^&']+)(&|$|')/",
                        $link,
                        $matches
                    )
                ) {
                    $idKspPersonne = $matches["id_ksp_personne"];
                }

                $isPersonneLiee = false;
                $dateNaissance = null;

                if ("PL" == $codeStatut) {
                    $isPersonneLiee = true;
                } else {
                    $dateNaissance = \DateTime::createFromFormat(
                        "%d/%m/%Y",
                        $personneFields->eq(2)->text()
                    );
                }
                
                $role = self::trim(explode("-", self::trim($personneFields->eq(5)->text()))[0]);


                $personneData = [
                    "is_personne_liee" => $isPersonneLiee,
                    "link" => $link,
                    "id_ksp" => $idKspPersonne,
                    "code_statut" => $codeStatut,
                    "nom_complet" => $linkField->text(),
                    "date_naissance" => $dateNaissance,
                    "b1" => $personneFields->eq(3)->text(),
                    "mineur" => preg_match(
                        "/Min/i",
                        self::trim($personneFields->eq(4)->text())
                    ),
                    "role" => $role,
                    "dup" => $personneFields->eq(6)->text(),
                    "categorie_penale" => $personneFields->eq(7)->text(),
                    "avocat" => $personneFields->eq(8)->text(),
                ];

                $personnes[] = $personneData;
            });

        return $personnes;
    }

    private function parsePersonneDetails(Crawler $crawler, $personne)
    {
        $personne["id_ksp"] = self::trim($crawler->filter('input[name=identifierPersonne]')->getNode(0)->getAttribute('value'));

        //type personne
        $personne["nature"] = self::trim($crawler
            ->filter("#travail table.tile thead")
            ->eq(0)
            ->text());

        $type = null;

        if ($personne["nature"] == "Personne élement de structure") {
            $type = "ElementStructure";
        } else if ($personne["nature"] == "Personne morale") {
            $type = "Morale";
        } else {
            $type = $personne["mineur"] ? "Mineure" : "Majeure";
        }

        $personne["type"] = $type;

        $etatCivilLink = urldecode(
            urldecode(
                $crawler->filter(".arbre4 a")->getNode(0)->getAttribute("href")
            )
        );

        $personne["etat_civil_link"] = preg_replace(
            "/^javascript:goFromMenu\('\/cassiopee\/(.*)'\);$/",
            '$1',
            $etatCivilLink
        );

        $personne["natinfs"] = [];

        //liens natinfs
        $crawler
            ->filter("#listInfractionAuteur table.tile tr.odd")
            ->each(function ($natinf) use (&$personne) {
                $natinfFields = $natinf->filter("td.list");

                $rang = $natinfFields->eq(0)->text();
                $link = $natinfFields
                    ->eq(2)
                    ->filter("a")
                    ->getNode(0)
                    ->getAttribute("href");
                $natinfId = preg_replace(
                    "/^.*identifier=(.*)['&].*$/",
                    '$1',
                    $link
                );

                $modaliteParticipationReferentiel = [
                    ["mnemo" => "C", "code" => "2", "libelle" => "complicité"],
                    [
                        "mnemo" => "CT",
                        "code" => "10",
                        "libelle" => "complicité de tentative",
                    ],
                    [
                        "mnemo" => "CTR",
                        "code" => "13",
                        "libelle" => "complicité de tentative en récidive",
                    ],
                    [
                        "mnemo" => "CR",
                        "code" => "12",
                        "libelle" => "complicité en récidive",
                    ],
                    [
                        "mnemo" => "IFD",
                        "code" => "8",
                        "libelle" => "intérêt à la fraude douanière",
                    ],
                    ["mnemo" => "R", "code" => "3", "libelle" => "récidive"],
                    ["mnemo" => "T", "code" => "1", "libelle" => "tentative"],
                    [
                        "mnemo" => "TR",
                        "code" => "11",
                        "libelle" => "tentative en récidive",
                    ],
                    [
                        "mnemo" => "CTNR",
                        "code" => "17",
                        "libelle" =>
                            "complicité de tentative en nouvelle récidive",
                    ],
                    [
                        "mnemo" => "CNR",
                        "code" => "15",
                        "libelle" => "complicité en nouvelle récidive",
                    ],
                    [
                        "mnemo" => "NR",
                        "code" => "14",
                        "libelle" => "nouvelle récidive",
                    ],
                    [
                        "mnemo" => "TNR",
                        "code" => "7",
                        "libelle" => "tentative nouvelle récidive",
                    ],
                ];

                $modaliteParticipationMnemo = trim(
                    $natinfFields->eq(4)->text(),
                    " \n\r\t\v\x00\xc2\xa0"
                );

                $modaliteParticipation = null;

                foreach ($modaliteParticipationReferentiel as $referentiel) {
                    if ($referentiel["mnemo"] === $modaliteParticipationMnemo) {
                        $modaliteParticipation = $referentiel;
                        break;
                    }
                }

                $personne["natinfs"][] = [
                    "id_ksp" => $natinfId,
                    "rang" => $rang,
                    "modalite_participation" => $modaliteParticipation,
                ];
            });

        return $personne;
    }

    private function parsePersonnePhysiqueDetails(Crawler $crawler, $personne)
    {
        $formCrawler = $crawler->filter("form");
        if (count($formCrawler) <= 0) {
            return $personne;
        }

        $mapping = [
            "nom" => ["identiteNomNaissance", "nomNaissance"],
            "raison_sociale" => "raisonSociale",
            "enseigne" => "enseigne",
            "siren_siret" => "siren",
            "sigle" => "sigle",
            "nom_usage" => ["identiteNomUsage", "nomUsage"],
            "prenom_1" => ["identitePrenom1", "prenom"],
            "prenom_2" => "identitePrenom2",
            "prenom_3" => "identitePrenom3",
            "code_barre_fnaeg" => "codeBarreFNAEG",
            "telephone" => "telephone",
            "portable" => "telephonePortable",
            "courriel" => "email",
            "commune_naissance" => [
                "code" => "codeInseeCommuneNaissance",
                "libelle" => "nomCommuneNaissance",
            ],
            "pays_naissance" => [
                "code" => "naissanceCodePays",
                "libelle" => "naissancePays",
            ],
            "langue_parle" => [
                "code" => "codeLangueParlee",
                "libelle" => "libelleLangueParlee",
            ],
            "nationalite" => [
                "code" => "naissanceCodeNationalite1",
                "libelle" => "naissanceNationalite1",
            ],
            "nationalite2" => [
                "code" => "naissanceCodeNationalite2",
                "libelle" => "naissanceNationalite2",
            ],
            "categorie_penale" => "codeCategoriePenale",
            "antecedent_judiciaire" => "antecedentsJudiciaires",
            "civilite" => "identiteCivilite",
            "forme_juridique" => "formeJuridique",
            "sans_domicile" => "blocAdresseForm\\.domicileSansDomicile",
            "pere" => [
                "nom" => "filiationNomPere",
                "prenoms" => "filiationPrenomsPere",
            ],
            "mere" => [
                "nom" => "filiationNomMere",
                "prenoms" => "filiationPrenomsMere",
            ],
            "x_se_disant" => "seDisant",
            "defere" => "defere",
            "aj" => "aj",
            "declaration_adresse" => "blocAdresseForm\\.adresseDeclaree",
            "date_naissance" => [
                "year" => "dateNaissance\\.dateAnnee",
                "month" => "dateNaissance\\.dateMois",
                "day" => "dateNaissance\\.dateJour",
            ],
            "date_deces" => [
                "year" => "dateDeces_2",
                "month" => "dateDeces_1",
                "day" => "dateDeces_0",
            ],
            "date_deferement" => [
                "year" => "dateDeferement_2",
                "month" => "dateDeferement_1",
                "day" => "dateDeferement_0",
            ],
            "date_declaration_adresse" => [
                "year" => "blocAdresseForm\\.dateDeclarationAdr_2",
                "month" => "blocAdresseForm\\.dateDeclarationAdr_1",
                "day" => "blocAdresseForm\\.dateDeclarationAdr_0",
            ],
            "adresse" => [
                "ligne_1" => "blocAdresseForm\\.adresseLigne1",
                "ligne_2" => "blocAdresseForm\\.adresseLigne2",
                "ligne_3" => "blocAdresseForm\\.adresseLigne3",
                "lieu_dit" => "blocAdresseForm\\.lieuDit",
                "code_postal" => "blocAdresseForm\\.codePostal",
                "localite" => "blocAdresseForm\\.nomCommune",
                "pays" => [
                    "code" => "blocAdresseForm\\.adresseCodePays",
                    "libelle" => "blocAdresseForm\\.pays",
                ],
            ],
        ];

        return self::mapForm($crawler, $mapping, $personne);
    }

    public function parseNatinfDetails($crawler, $natinf)
    {
        $mapping = [
            "lieu" => "lieu",
            "commune" => [
                "code" => "codeInsee",
                "libelle" => "nomCommune",
            ],
            "code" => "natInf",
            "libelle" => "qs",
            "debut" => [
                "operateur" => "dateIncompleteDebut\\.prefixe",
                "year" => "dateIncompleteDebut\\.dateAnnee",
                "month" => "dateIncompleteDebut\\.dateMois",
                "day" => "dateIncompleteDebut\\.dateJour",
                "hour" => "dateIncompleteDebut\\.dateHeure",
                "minute" => "dateIncompleteDebut\\.dateMinutes",
                "second" => "dateIncompleteDebut\\.dateSecondes",
            ],
            "fin" => [
                "operateur" => "dateIncompleteFin\\.prefixe",
                "year" => "dateIncompleteFin\\.dateAnnee",
                "month" => "dateIncompleteFin\\.dateMois",
                "day" => "dateIncompleteFin\\.dateJour",
                "hour" => "heureFinHeure",
                "minute" => "heureFinMinutes",
            ],
        ];

        return self::mapForm($crawler, $mapping, $natinf);
    }

    private function parsePersonnesLiees(Crawler $crawler, $personne)
    {
        $personnesLiees = $crawler->filter("#tbl-container-kcop tr.odd");

        $personnesLiees->each(function ($personneLiee) use (&$personne) {
            $personneLieeFields = $personneLiee->filter("td.list");

            $link = $personneLieeFields
                ->eq(1)
                ->filter("a")
                ->getNode(0)
                ->getAttribute("href");

            $idKsp = preg_replace(
                "/.*identifierPersonne=([0-9]*,[0-9]*,[0-9]*).*/",
                '$1',
                $link
            );

            $lienJuridique = $personneLieeFields
                ->eq(2)
                ->filter("option[selected]");

            $lienSocial = $personneLieeFields
                ->eq(3)
                ->filter("option[selected]");

            for ($i = 0; $i < count($personne["personnes_liees"]); $i++) {
                $pl = $personne["personnes_liees"][$i];

                
                if ($pl["id_ksp"] !== $idKsp) {
                    $this->logger->error($pl["id_ksp"] . " != " . $idKsp);
                    continue;
                }


                $pl["lien_juridique"] = [
                    "code" => $lienJuridique->getNode(0)->getAttribute("value"),
                    "libelle" => $lienJuridique->text(),
                ];

                $pl["lien_social"] = [
                    "code" => $lienSocial->getNode(0)->getAttribute("value"),
                    "libelle" => $lienSocial->text(),
                ];

                $personne["personnes_liees"][$i] = $pl;

                break;
            }
        });

        return $personne;
    }

    private function parsePersonneModePoursuite($crawler, $personne) {
        $personne["mode_poursuite"] = null;

        $crawler
            ->filter('tbody tr')
            ->each(function ($evt) use (&$personne) {
                $evtFields = $evt->filter('td');

                if (count($evtFields) !== 6) {
                    return ;
                }

                $date = self::trim($evtFields->eq(0)->text());
                $date = \DateTime::createFromFormat('d/m/Y', $date);
                $mode = self::trim($evtFields->eq(2)->text());

                preg_match('/([A-Z-]+) - (.*)/', $mode, $matches);

                if ($matches) {
                    $mode = [
                        "code" => $matches[1],
                        "libelle" => $matches[2]
                    ];
                } else {
                    $mode = [
                        "code" => $mode,
                        "libelle" => $mode
                    ];
                }

                if (!$personne["mode_poursuite"] || $personne["mode_poursuite"]["date"] < $date) {
                    $personne["mode_poursuite"] = [
                        "date" => $date,
                        "code" => self::trim($mode["code"]),
                        "libelle" => self::trim($mode["libelle"])
                    ];
                }
            });

        return $personne;
    }

    public function getAffaireDetails(
        string $affaireId,
        string $parquetId,
        string $type,
        string $numeroParquet
    ) {
        $this->restoreCookies();

        $browser = $this->getBrowser();
        $browser->followRedirects(false);
        //using raw client to run requests asynchronously when possible
        $httpClient = $this->createClientWithCookies();

        $mainResponse = $httpClient->request(
            "GET",
            $this->url(Env::get("CASSIOPEE_AFFAIRE")) .
                "&identifierAffaire=$affaireId" .
                "&identifierIdenParquet=$parquetId" .
                "&typeAffaire=$type" .
                "&numeroParquet=$numeroParquet"
        );
        $personnesResponse = $httpClient->request(
            "GET",
            $this->url(
                Env::get("CASSIOPEE_AFFAIRE_PERSONNES") .
                    "&identifierAffaire=$affaireId" .
                    "&numeroParquet=$numeroParquet"
            )
        );
        $natinfResponse = $httpClient->request(
            "GET",
            $this->url(
                Env::get("CASSIOPEE_AFFAIRE_NATINFS") .
                    "&identifierAffaire=$affaireId"
            )
        );

        \Fiber::suspend();

        $natinfCrawler = new Crawler(null, $natinfResponse->getInfo("url"));
        $natinfCrawler->add($natinfResponse->getContent());

        $natinfs = $this->browsePages(
            $browser,
            $natinfCrawler,
            "listInfraction",
            fn($paginationCrawler) => $this->parseNatinfs($paginationCrawler)
        );

        $personnesCrawler = new Crawler(
            null,
            $personnesResponse->getInfo("url")
        );
        $personnesCrawler->add($personnesResponse->getContent());

        $personnes = $this->browsePages(
            $browser,
            $personnesCrawler,
            "listPersonne",
            fn($paginationCrawler) => $this->parsePersonnes($paginationCrawler)
        );

        $mainCrawler = new Crawler(null, $mainResponse->getInfo("url"));
        $mainCrawler->addContent($mainResponse->getContent());

        $formCrawler = $mainCrawler->filter("form[name=syntheseAffaireForm],form[name=resumeAffaireRequeteForm]");

        $affaire = $this->parseAffaireDetails($formCrawler);

        $affaireId = $affaire["id_ksp_affaire"];
        $type = $affaire["type"];


        $affaire["nataffs"] = $this->parseNataffs($formCrawler->form());

        unset($form);
        unset($mainCrawler);
        unset($mainResponse);

        $personneDetailsResponses = array_map(function ($personne) use (
            &$httpClient,
            $affaireId,
            &$numeroParquet,
            &$type,
            &$parquetId
        ) {
            return $httpClient->request(
                "POST",
                $this->url(
                    Env::get("CASSIOPEE_PERSONNE") .
                        "&identifierAffaire=$affaireId" .
                        "&rolePersonne=" .
                        $personne["code_statut"] .
                        "&identifierPersonne=" .
                        $personne["id_ksp"] .
                        "&numeroParquet=$numeroParquet" .
                        "&typeAffaire=$type" .
                        "&identifierIdenParquet=$parquetId"
                )
            );
        }, $personnes);


        $natinfResponses = array_map(function ($natinf) use (
            &$httpClient,
            &$affaireId,
            &$parquetId
        ) {
            return $httpClient->request(
                "GET",
                $this->url(
                    ENV::get("CASSIOPEE_NATINF") .
                        "&identifier=" .
                        $natinf["id_ksp"] .
                        "&identifierAffaire=" .
                        $affaireId .
                        "&identifierIdenParquet=" .
                        $parquetId
                )
            );
        }, $natinfs);

        \Fiber::suspend();

        for ($i = 0; $i < count($personneDetailsResponses); $i++) {
            $response = $personneDetailsResponses[$i];
            $personne = $personnes[$i];

            $personneDetailsCrawler = new Crawler(
                null,
                $response->getInfo("url")
            );
            $personneDetailsCrawler->addContent($response->getContent());
            $personnes[$i] = $this->parsePersonneDetails(
                $personneDetailsCrawler,
                $personne
            );
            unset($personneDetailsCrawler);

        }
        unset($personneDetailsResponses);

        $personneModePoursuiteResponses = array_map(function ($personne) use (
            &$httpClient,
            $affaireId,
            &$numeroParquet,
            &$type,
            &$parquetId
        ) {
            if (in_array($personne["role"], ["Prévenu", "Prévenu - A"])) {
                return $httpClient->request(
                    'GET',
                    $this->url(
                        Env::get('CASSIOPEE_PREVENU_MODE_POURSUITE') .
                        "&identifierAffaire=$affaireId" .
                        "&identifierIdenParquet=$parquetId" .
                        "&identifierPersonne=" . $personne["id_ksp"] .
                        "&familleEvt=SAISJGT"
                    )
                );
            } else {
                return null;
            }
        }, $personnes);

        $personneResponses = array_map(function ($personne) use (
            &$httpClient,
            $affaireId,
            &$numeroParquet,
            &$type,
            &$parquetId
        ) {
            return $httpClient->request(
                "POST",
                $this->url($personne["etat_civil_link"])
            );
        }, $personnes);

        \Fiber::suspend();

        for ($i = 0; $i < count($personneResponses); $i++) {
            $response = $personneResponses[$i];
            $personne = $personnes[$i];

            $personneDetailsCrawler = new Crawler(
                null,
                $response->getInfo("url")
            );
            $personneDetailsCrawler->addContent($response->getContent());
            $personnes[$i] = $this->parsePersonnePhysiqueDetails(
                $personneDetailsCrawler,
                $personne
            );
            unset($personneDetailsCrawler);

            if ($personneModePoursuiteResponses[$i]) {
                $modePoursuiteCrawler = new Crawler(
                    null,
                    $personneModePoursuiteResponses[$i]->getInfo('url')
                );
                $modePoursuiteCrawler->addContent($personneModePoursuiteResponses[$i]->getContent());

                $personnes[$i] = $this->parsePersonneModePoursuite(
                    $modePoursuiteCrawler,
                    $personnes[$i]
                );
            }
        }
        unset($personneResponses);

        //match "personnes liées" with their corresponding "personnes"
        $currentPersonneId = -1;
        for ($i = 0; $i < count($personnes); $i++) {
            if (!$personnes[$i]["is_personne_liee"]) {
                $currentPersonneId = $i;
                $personnes[$i]["personnes_liees"] = [];
            } elseif ($currentPersonneId >= 0) {
                $personnes[$currentPersonneId]["personnes_liees"][] =
                    $personnes[$i];
            }
        }
        $personnes = array_values(
            array_filter(
                $personnes,
                fn($personne) => !$personne["is_personne_liee"]
            )
        );

        for ($i = 0; $i < count($personnes); $i++) {
            $personne = $personnes[$i];

            if (count($personne["personnes_liees"]) <= 0) {
                continue;
            }
           
            $personneLieeCrawler = $browser->request(
                "GET",
                $this->url(
                    Env::get("CASSIOPEE_PERSONNES_LIEES") .
                        "&identifierAffaire=$affaireId" .
                        "&numeroParquet=". 
                        substr($numeroParquet, 2) .
                        "&rolePersonne=" .
                        $personne["code_statut"] .
                        "&typeAffaire=$type" .
                        "&identifierIdenParquet=$parquetId" .
                        "&identifierPersonne=" .
                        $personne["id_ksp"] .
                        "&typePersonne=" .
                        $personne["type"]
                )
            );

            $personnes[$i] = $this->parsePersonnesLiees($personneLieeCrawler, $personne);
        }

        \Fiber::suspend();

        $affaire["personnes"] = $personnes;

        for ($i = 0; $i < count($natinfResponses); $i++) {
            $response = $natinfResponses[$i];
            $natinf = $natinfs[$i];

            $natinfDetailsCrawler = new Crawler(
                null,
                $response->getInfo("url")
            );
            $natinfDetailsCrawler->addContent($response->getContent());
            $natinfs[$i] = $this->parseNatinfDetails(
                $natinfDetailsCrawler,
                $natinf
            );
        }

        $affaire["natinfs"] = $natinfs;

        return $affaire;
    }

    public function getNatinfDetails(array $natinf, HttpBrowser $browser)
    {
        $crawler = $browser->request("GET", $this->url($natinf["link"]));
    }
}
