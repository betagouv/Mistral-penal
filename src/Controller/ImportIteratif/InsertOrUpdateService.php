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
namespace App\Controller\ImportIteratif;

use App\Contracts\ReferentielEntityInterface;
use App\Entity\Adresse;
use App\Entity\Affaire;
use App\Entity\AffaireNatinf;
use App\Entity\AffairePersonne;
use App\Entity\AntecedentJudiciaire;
use App\Entity\Audience;
use App\Entity\CategoriePenale;
use App\Entity\Civilite;
use App\Entity\Commune;
use App\Entity\FormeJuridique;
use App\Entity\HorodatageFait;
use App\Entity\Intervenant;
use App\Entity\Langue;
use App\Entity\LienJuridique;
use App\Entity\LienSocial;
use App\Entity\ModaliteParticipation;
use App\Entity\ModePoursuite;
use App\Entity\Nataff;
use App\Entity\Natinf;
use App\Entity\NatinfPersonne;
use App\Entity\Nationalite;
use App\Entity\OperateurHorodatage;
use App\Entity\Parente;
use App\Entity\Pays;
use App\Entity\Personne;
use App\Entity\RepresentantLegal;
use App\Entity\SansDomicile;
use App\Entity\Security\Account;
use App\Entity\Security\Service;
use App\Entity\StatutPersonne;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InsertOrUpdateService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function insert_or_update_audience(
        Account $user,
        EntityManagerInterface $em,
        array $params
    ): array {
        $idKsp = $params["id_cassiopee"];
        $date = $params["date"];
        $serviceLabel = $params["libelleService"];

        $service = $em
            ->getRepository(Service::class)
            ->findOneByUserAndLabel($user, $serviceLabel);

        $specialite = $params["specialite"] ?? null;

        $ar = $em->getRepository(Audience::class);

        $audience = $ar->findOneBy([
            "idKsp" => $idKsp,
        ]);
        if (null === $audience) {

            if ($service == null) {
                throw new \Exception("Cannot find service with label \"" . $serviceLabel . "\" !");
            }

            $audience = new Audience();
            $audience
                ->setIdKsp($idKsp)
                ->setDate($date)
                ->setService($service);
        }

        $audience->setDebut($params["heure_debut"] ?? null);
        $audience->setEvaluatedTime($params["duree_evaluee"] ?? null);
        $audience->setEstimatedTime($params["duree_theorique"] ?? null);
        $audience->setMaxNumberOfFolders(
            intval($params["quantite_affaires"])
        );
        $audience->setDateDernierImport(new \DateTime());
        $audience->setSpecialty($specialite);

        $ar->save($audience, true);

        $intervenants = $params["intervenants"] ?? [];

        $intervenants = array_map(
            fn($intervenant) => $this->insert_or_update_intervenant(
                em: $em,
                audience: $audience,
                params: $intervenant
            ),
            $intervenants
        );

        return ["audience" => $audience, "intervenants" => $intervenants];
    }

    public function insert_or_update_intervenant(
        EntityManagerInterface $em,
        Audience $audience,
        array $params
    ): ?Intervenant {
        $cr = $em->getRepository(Intervenant::class);

        $idKsp = $params["id_ksp"] ?? null;
        $role = $params["role"] ?? null;
        $libelleComplet = $params["libelle_complet"] ?? null;

        if (null === $idKsp) {
            return null;
        }

        $intervenant = $cr->findOneBy(["idKsp" => $idKsp]);

        if (null === $intervenant) {
            $intervenant = new Intervenant();
            $intervenant->setIdKsp($idKsp);
        }

        $intervenant->setRole($role);
        $intervenant->setNomComplet($libelleComplet);
        $intervenant->addAudience($audience);

        $audience->addIntervenant($intervenant);

        $cr->save($intervenant, true);

        return $intervenant;
    }

    public function insert_or_update_affaire(
        EntityManagerInterface $em,
        Audience $audience,
        array $params,
        array $intervenants = []
    ): ?Affaire {
        //self::format_params($params);

        $ar = $em->getRepository(Affaire::class);

        $idKsp = $params["id_ksp_affaire"] ?? null;

        if (null === $idKsp) {
            return null;
        }

        $affaire = $ar->findOneBy(["idKsp" => $idKsp]);

        if (null === $affaire) {
            $affaire = new Affaire();
            $affaire->setIdKsp($idKsp);

            $position = $ar->getNextPositionByAudience($audience);
            $affaire->setPosition($position);
        }

        $affaire->setType($params["type"] ?? null);
        $affaire->setNumeroParquet($params["numero_parquet"] ?? null);
        $affaire->setNumeroCabinet($params["numero_cabinet"] ?? null);
        $affaire->setNature($params["nature_procedure"] ?? null);
        $affaire->setNombreDetenuConvoque(
            intval($params["nombre_detenu_convoque"])
        );
        $affaire->setNombrePrevenuConvoque(
            intval($params["nombre_prevenu_convoque"])
        );
        $affaire->setDuree($params["duree"] ?? null);
        $affaire->setIdentifiantJustice($params["identifiant_justice"] ?? null);
        $affaire->setParquetierEnCharge(
            $params["parquetier_en_charge"] ?? null
        );
        $affaire->setServiceEnCharge($params["service_en_charge"] ?? null);
        $affaire->setTypeInfraction($params["type_infraction"] ?? null);
        $affaire->setEmetteur($params["emetteur"] ?? null);
        $affaire->setActeSaisine($params["acte_saisine"] ?? null);
        $affaire->setNombreVehicule(intval($params["nombre_vehicule"]));
        $affaire->setScelle((bool) ($params["scelle"] ?? false));
        $affaire->setScelleAgrasc((bool) ($params["scelle_agrasc"] ?? false));
        $affaire->setOrigine($params["origine"] ?? null);
        $affaire->setIsJIRS($params["affaire_jirs"] ?? false);
        $affaire->setIsPlainteEnLigne($params["plainte_en_ligne"] ?? false);
        $affaire->setIsAccesPnat($params["acces_pnat"] ?? false);
        $affaire->setIsAccesPnf($params["acces_pnf"] ?? false);
        $affaire->setIsPoleInstruction($params["pole_instruction"] ?? false);
        $affaire->setIsEurojust($params["affaire_eurojust"] ?? false);
        $affaire->setAudience($audience);

        $strDateSaisine = $params["date_saisine"] ?? null;
        $affaire->setDateSaisine(null);

        if ($strDateSaisine) {
            $affaire->setDateSaisine(new \DateTime($strDateSaisine));
        }

        foreach ($intervenants as $intervenant) {
            $affaire->setIntervenant($intervenant);
        }

        $ar->save($affaire, true);

        $nataffs = $params["nataffs"] ?? [];

        array_map(
            fn($nataff) => $this->insert_or_update_nataff(
                em: $em,
                affaire: $affaire,
                params: $nataff
            ),
            $nataffs
        );

        /** @var array $affairePersonnes */
        $affairePersonnes = $params["personnes"] ?? [];

        foreach ($affairePersonnes as $tAffairePersonne) {
            $this->insert_or_update_affaire_personne(
                em: $em,
                affaire: $affaire,
                params: $tAffairePersonne
            );

            if (!$tAffairePersonne["personnes_liees"]) {
                continue;
            }

            foreach (
                $tAffairePersonne["personnes_liees"]
                as $affairePersonneLiee
            ) {
                $this->insert_or_update_affaire_personne(
                    em: $em,
                    affaire: $affaire,
                    params: $affairePersonneLiee
                );
            }
        }

        $affaireNatinfs =
            !empty($params["natinfs"]) && is_array($params["natinfs"])
                ? $params["natinfs"]
                : [];
        foreach ($affaireNatinfs as $tAffaireNatinf) {
            $affaireNatinf = $this->insert_or_update_affaire_natinf(
                em: $em,
                affaire: $affaire,
                params: $tAffaireNatinf
            );

            foreach ($params["personnes"] as $personne) {
                foreach ($personne["natinfs"] as $natinf) {
                    if ($natinf["id_ksp"] === $affaireNatinf->getIdKsp()) {
                        $this->insert_or_update_natinf_personne_into_natinf(
                            em: $em,
                            affaireNatinf: $affaireNatinf,
                            params: [
                                "personne" => $personne,
                                "natinf" => $natinf,
                            ]
                        );
                    }
                }
            }
        }

        foreach ($params["personnes"] as $personne) {
            foreach ($personne["personnes_liees"] as $personneLiee) {
                if (!array_key_exists("lien_juridique", $personneLiee)) {
                    $this->logger->warning("missing lien juridique ! passing personne " . $personne["nom_complet"] . " for affaire " . $affaire->getNumeroParquet());
                }

                $this->insert_or_update_relation(
                    em: $em,
                    affaire: $affaire,
                    params: [
                        "represente" => [
                            "id_ksp" => $personne["id_ksp"],
                        ],
                        "representant" => [
                            "id_ksp" => $personneLiee["id_ksp"],
                        ],
                        "lien_juridique" => array_key_exists("lien_juridique", $personneLiee) ? $personneLiee["lien_juridique"] : null,
                        "lien_social" => array_key_exists("lien_social", $personneLiee) ? $personneLiee["lien_social"] : null,
                        "statut" => $personneLiee["code_statut"],
                    ]
                );
            }
        }

        $ar->save($affaire, true);

        return $affaire;
    }

    public function insert_or_update_nataff(
        EntityManagerInterface $em,
        Affaire $affaire,
        array $params
    ): Nataff {
        $repo = $em->getRepository(Nataff::class);
        $nataff = self::insert_or_update_referentiel(
            em: $em,
            params: $params,
            entityClass: Nataff::class
        );
        $affaire->addNataff($nataff);
        $nataff->addAffaire($affaire);
        $repo->save($nataff, true);
        return $nataff;
    }

    public function insert_or_update_affaire_personne(
        EntityManagerInterface $em,
        Affaire $affaire,
        array $params
    ): ?AffairePersonne {
        $apr = $em->getRepository(AffairePersonne::class);

        $personne = $this->insert_or_update_personne(
            em: $em,
            affaire: $affaire,
            params: $params ?? []
        );
        if (null === $personne) {
            return null;
        }

        $affairePersonne = $apr->findOneBy([
            "affaire" => $affaire,
            "personne" => $personne,
        ]);
        if (null === $affairePersonne) {
            $affairePersonne = new AffairePersonne();
            $affairePersonne->setAffaire($affaire);
            $affairePersonne->setPersonne($personne);
            $affaire->addAffairePersonne($affairePersonne);
            $em->persist($affairePersonne);
        }

        $affairePersonne->setNomComplet($params["nom_complet"] ?? null);
        $affairePersonne->setB1($params["b1"] ?? null);
        $affairePersonne->setMineur($params["mineur"] ?? null);
        $affairePersonne->setDup($params["dup"] ?? null);
        $affairePersonne->setCategoriePenale(
            $params["categorie_penale"]["libelle"] ?? null
        );
        $affairePersonne->setAvocat($params["avocat"] ?? null);
        $affairePersonne->setIsDefere($params["defere"] ?? null);
        $affairePersonne->setAj($params["aj"] ?? null);
        $strDateDeferement = $params["date_deferement"] ?? null;

        if (is_array($strDateDeferement) 
            && array_key_exists("year", $strDateDeferement)
            && array_key_exists("month", $strDateDeferement)
            && array_key_exists("day", $strDateDeferement)) {
            $dateDeferement = new \DateTime();
            $dateDeferement->setDate($strDateDeferement["year"], $strDateDeferement["month"], $strDateDeferement["day"]);
            
            $affairePersonne->setDateDeferement($dateDeferement);
        }

        /**
         * @author yanroussel
         *
         * @comment Le code est partagé par plusieurs libellés (ex: 'A' correspond à "Prévenu" et "jugé").
         *          On remplace en conséquence le code par le libellé
         */
        $statutPersonne = $this->insert_or_update_referentiel(
            em: $em,
            params: [
                "code" => $params["role"],
                "libelle" => $params["role"],
            ],
            entityClass: StatutPersonne::class
        );
        $affairePersonne->setStatut($statutPersonne);

        if (array_key_exists("mode_poursuite", $params) && !empty($params["mode_poursuite"])) {
            $modePoursuite = $this->insert_or_update_referentiel(
                em: $em,
                params: $params["mode_poursuite"],
                entityClass: ModePoursuite::class
            );

            $affairePersonne->setModePoursuite($modePoursuite);
        }

        $em->persist($affairePersonne);
        $em->flush();

        return $affairePersonne;
    }

    public function insert_or_update_personne(
        EntityManagerInterface $em,
        Affaire $affaire,
        array $params
    ): ?Personne {
        $pr = $em->getRepository(Personne::class);
        $idKsp = $params["id_ksp"] ?? null;
        if (null === $idKsp) {
            return null;
        }

        $personne = $pr->findOneBy(["idKsp" => $idKsp]);
        if (null === $personne) {
            $personne = new Personne($em);
            $personne->setIdKsp($idKsp);
        }

        $personne->setNom($params["nom"] ?? null);

        $personne->setSigle($params["sigle"] ?? null);
        $personne->setRaisonSociale($params["raison_sociale"] ?? null);
        $personne->setSirenSiret($params["siren_siret"] ?? null);
        $personne->setEnseigne($params["enseigne"] ?? null);
        $isPersonneMorale =
            !empty($params["sigle"]) ||
            !empty($params["raison_sociale"]) ||
            !empty($params["siren_siret"]) ||
            !empty($params["enseigne"]);
        $personne->setIsPersonneMorale($isPersonneMorale);
        $personne->setNomUsage($params["nom_usage"] ?? null);
        $personne->setPrenom1($params["prenom_1"] ?? null);
        $personne->setPrenom2($params["prenom_2"] ?? null);
        $personne->setPrenom3($params["prenom_3"] ?? null);
        $personne->setCodeBarreFnaeg($params["prenom_3"] ?? null);
        $personne->setTelephone($params["telephone"] ?? null);
        $personne->setPortable($params["portable"] ?? null);
        $personne->setCourriel($params["courriel"] ?? null);
        $formeJuridique = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["forme_juridique"])
                ? $params["forme_juridique"]
                : [],
            entityClass: FormeJuridique::class
        );
        $personne->setFormeJuridique($formeJuridique);
        $communeNaissance = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["commune_naissance"])
                ? $params["commune_naissance"]
                : [],
            entityClass: Commune::class
        );
        $personne->setCommuneNaissance($communeNaissance);
        $paysNaissance = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["pays_naissance"])
                ? $params["pays_naissance"]
                : [],
            entityClass: Pays::class
        );
        $personne->setPaysNaissance($paysNaissance);
        $langueParlee = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["langue_parle"])
                ? $params["langue_parle"]
                : [],
            entityClass: Langue::class
        );
        $personne->setLangueParlee($langueParlee);
        $nationalite = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["nationalite"])
                ? $params["nationalite"]
                : [],
            entityClass: Nationalite::class
        );
        $personne->setNationalite($nationalite);
        $nationalite2 = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["nationalite2"])
                ? $params["nationalite2"]
                : [],
            entityClass: Nationalite::class
        );
        $personne->setNationalite2($nationalite2);
        $categoriePenale = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["categorie_penale"])
                ? $params["categorie_penale"]
                : [],
            entityClass: CategoriePenale::class
        );
        $personne->setCategoriePenale($categoriePenale);
        $civilite = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["civilite"]) ? $params["civilite"] : [],
            entityClass: Civilite::class
        );
        $personne->setCivilite($civilite);
        $antecedentJudiciaire = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["antecedent_judiciaire"])
                ? $params["antecedent_judiciaire"]
                : [],
            entityClass: AntecedentJudiciaire::class
        );
        $personne->setAntecedentJudiciaire($antecedentJudiciaire);
        $sansDomicile = self::insert_or_update_referentiel(
            em: $em,
            params: is_array($params["sans_domicile"])
                ? $params["sans_domicile"]
                : [],
            entityClass: SansDomicile::class
        );
        $personne->setSansDomicile($sansDomicile);

        $pereNom = $params["pere"]["nom"] ?? null;
        $perePrenoms = $params["pere"]["prenoms"] ?? null;

        $pere = $personne->getPere();
        if (null === $pere) {
            $pere = new Parente();
            $em->persist($pere);
            $personne->setPere($pere);
        }
        $pere->setNom($pereNom);
        $pere->setPrenoms($perePrenoms);
        $em->persist($pere);

        $mereNom = $params["mere"]["nom"] ?? null;
        $merePrenoms = $params["mere"]["prenoms"] ?? null;
        $mere = $personne->getMere();
        if (null === $mere) {
            $mere = new Parente();
            $em->persist($mere);
            $personne->setMere($mere);
        }
        $mere->setNom($mereNom);
        $mere->setPrenoms($merePrenoms);
        $em->persist($mere);

        $personne->setXSeDisant($params["x_se_disant"] ?? false);
        $personne->setDeclarationAdresse(
            $params["declaration_adresse"] ?? false
        );
        $dates = [
            "date_naissance" => "DateNaissance",
            "date_deces" => "DateDeces",
            "date_declaration_adresse" => "DateDeclarationAdresse",
        ];
        foreach ($dates as $dateKey => $dateField) {
            $val = $params[$dateKey] ?? null;

            if (empty($val)) {
                continue;
            }

            $date = null;
            if (is_array($val)) {
                $date = new \DateTime();
                if (
                    !is_numeric($val["year"])
                    || !is_numeric($val["month"])
                    || !is_numeric($val["day"])) {
                        continue ;
                }

                $date->setDate($val["year"], $val["month"], $val["day"]);
                $date->setTime(0, 0);
            } else {
                $date = new \DateTime($val);
            }

            $op = "set$dateField";
            $personne->$op($date);
        }

        $adresse = self::insert_or_update_adresse(
            $em,
            $personne,
            !empty($params["adresse"]) && is_array($params["adresse"])
                ? $params["adresse"]
                : null
        );

        $em->persist($adresse);

        $em->persist($personne);
        $em->flush();

        return $personne;
    }

    public function insert_or_update_adresse(
        EntityManagerInterface $em,
        Personne $personne,
        ?array $params
    ): ?Adresse {
        $repo = $em->getRepository(Adresse::class);
        $adresse = $personne->getAdresse();
        if (null === $adresse) {
            $adresse = new Adresse($em);
            $em->persist($adresse);
            $personne->setAdresse($adresse);
            $adresse->setPersonne($personne);
        }

        $adresse->setLigne1($params["ligne_1"] ?? null);
        $adresse->setLigne2($params["ligne_2"] ?? null);
        $adresse->setLigne3($params["ligne_3"] ?? null);
        $adresse->setLieuDit($params["lieu_dit"] ?? null);
        $adresse->setCodePostal($params["code_postal"] ?? null);
        $adresse->setLocalite($params["localite"] ?? null);

        $pays = $adresse->getPays();
        if (!empty($params["pays"]) && !empty($params["pays"]["libelle"])) {
            $pays = self::insert_or_update_referentiel(
                em: $em,
                params: is_array($params["pays"]) ? $params["pays"] : [],
                entityClass: Pays::class
            );
        }
        $adresse->setPays($pays);
        $repo->save($adresse, true);
        return $adresse;
    }

    public function insert_or_update_affaire_natinf(
        EntityManagerInterface $em,
        Affaire $affaire,
        array $params
    ): AffaireNatinf {
        $nr = $em->getRepository(AffaireNatinf::class);
        $natinf = $this->insert_or_update_referentiel(
            em: $em,
            params: $params,
            entityClass: Natinf::class
        );
        if (null === $natinf) {
            return null;
        }
        $tmp = $em->getRepository(AffaireNatinf::class);

        $affaireNatinf = $tmp->findOneBy([
            "affaire" => $affaire,
            "natinf" => $natinf,
        ]);

        if (null === $affaireNatinf) {
            $affaireNatinf = new AffaireNatinf();
            $affaireNatinf->setAffaire($affaire);
            $affaireNatinf->setNatinf($natinf);
            $em->persist($affaireNatinf);
        }

        $affaireNatinf->setIdKsp($params["id_ksp"]);

        $commune = $this->insert_or_update_referentiel(
            em: $em,
            params: is_array($params["commune"]) ? $params["commune"] : [],
            entityClass: Commune::class
        );
        $affaireNatinf->setCommune($commune);
        $affaireNatinf->setLieu($params["lieu"] ?? null);

        $debut = $this->insert_or_update_horodatage_faits(
            em: $em,
            fait: $affaireNatinf->getDebut(),
            params: is_array($params["debut"]) ? $params["debut"] : []
        );
        $affaireNatinf->setDebut($debut);

        $fin = $this->insert_or_update_horodatage_faits(
            em: $em,
            fait: $affaireNatinf->getFin(),
            params: is_array($params["fin"]) ? $params["fin"] : []
        );
        $affaireNatinf->setFin($fin);

        $nr->save($affaireNatinf, true);

        return $affaireNatinf;
    }

    public function insert_or_update_natinf_personne_into_natinf(
        EntityManagerInterface $em,
        AffaireNatinf $affaireNatinf,
        array $params
    ): ?NatinfPersonne {
        $npr = $em->getRepository(NatinfPersonne::class);
        $pr = $em->getRepository(Personne::class);
        $idKsp = $params["personne"]["id_ksp"] ?? null;
        if (null === $idKsp) {
            return null;
        }

        $personne = $pr->findOneBy(["idKsp" => $idKsp]);
        if (null === $personne) {
            return null;
        }

        $natinfPersonne = $npr->findOneBy([
            "affaireNatinf" => $affaireNatinf,
            "personne" => $personne,
        ]);

        if (null === $natinfPersonne) {
            $natinfPersonne = new NatinfPersonne();
            $natinfPersonne->setAffaireNatinf($affaireNatinf);
            $natinfPersonne->setPersonne($personne);
            $em->persist($natinfPersonne);
        }
        $natinfPersonne->setRang(intval($params["natinf"]["rang"] ?? null));
        $natinfPersonne->setStatut($params["personne"]["role"] ?? null);

        $modaliteParticipation = $this->insert_or_update_referentiel(
            em: $em,
            params: is_array($params["natinf"]["modalite_participation"])
                ? $params["natinf"]["modalite_participation"]
                : [],
            entityClass: ModaliteParticipation::class
        );
        $natinfPersonne->setModaliteParticipation($modaliteParticipation);
        $npr->save($natinfPersonne, true);

        return $natinfPersonne;
    }

    public function insert_or_update_horodatage_faits(
        EntityManagerInterface $em,
        ?HorodatageFait $fait,
        array $params
    ): ?HorodatageFait {
        if (empty($params)) {
            return null;
        }

        $operateur = $this->insert_or_update_referentiel(
            em: $em,
            params: is_array($params["operateur"]) ? $params["operateur"] : [],
            entityClass: OperateurHorodatage::class
        );

        if (
            array_key_exists("year", $params) &&
            array_key_exists("month", $params) &&
            array_key_exists("day", $params)
        ) {
            $date = (new \DateTime())->setDate(
                intval($params["year"]),
                intval($params["month"]),
                intval($params["day"])
            );
            if (
                array_key_exists("hour", $params) &&
                array_key_exists("minute", $params) &&
                gettype($params["hour"]) === "integer" &&
                gettype($params["minute"]) === "integer"
            ) {
                $date->setTime($params["hour"], $params["minute"]);
            }
        } else {
            $date = null;
        }
        if (null === $date) {
            return null;
        }

        if (null === $fait) {
            $fait = new HorodatageFait();
            $em->persist($fait);
        }
        $fait->setDate($date);
        $fait->setOperateur($operateur);
        $fait->setHeure($date);
        return $fait;
    }

    public function insert_or_update_relation(
        EntityManagerInterface $em,
        Affaire $affaire,
        array $params
    ): ?RepresentantLegal {
        $rlr = $em->getRepository(RepresentantLegal::class);
        $pr = $em->getRepository(Personne::class);
        /** @var ?Personne $representantPersonne */
        $representantPersonne = $pr->findOneBy([
            "idKsp" => $params["representant"]["id_ksp"],
        ]);
        if (null === $representantPersonne) {
            return null;
        }
        /** @var ?LienJuridique $lienJuridique */
        $lienJuridique =
            !empty($params["lien_juridique"]) &&
            !empty($params["lien_juridique"]["code"])
                ? $this->insert_or_update_referentiel(
                    em: $em,
                    params: is_array($params["lien_juridique"])
                        ? $params["lien_juridique"]
                        : [],
                    entityClass: LienJuridique::class
                )
                : null;

        /** @var ?LienSocial $lienSocial */
        $lienSocial =
            !empty($params["lien_social"]) &&
            !empty($params["lien_social"]["code"])
                ? $this->insert_or_update_referentiel(
                    em: $em,
                    params: is_array($params["lien_social"])
                        ? $params["lien_social"]
                        : [],
                    entityClass: LienSocial::class
                )
                : null;
        /** @var ?AffairePersonne $representant */
        $representant = $affaire->getAffairePersonneByPersonne(
            $representantPersonne
        );
        if (null === $representant) {
            return null;
        }

        /** @var ?Personne $representePersonne */
        $representePersonne = $pr->findOneBy([
            "idKsp" => $params["represente"]["id_ksp"],
        ]);
        if (null === $representePersonne) {
            return null;
        }

        /** @var ?AffairePersonne $represente */
        $represente = $affaire->getAffairePersonneByPersonne(
            $representePersonne
        );
        if (null === $represente) {
            return null;
        }

        /** @var ?RepresentantLegal $relation */
        $relation = $rlr->findOneBy([
            "represente" => $represente,
            "representant" => $representant,
        ]);
        if (null === $relation) {
            $relation = new RepresentantLegal();
            $relation->setRepresente($represente);
            $relation->setRepresentant($representant);
            $em->persist($relation);
        }
        $relation->setLienSocial($lienSocial);
        $relation->setLienJuridique($lienJuridique);
        $relation->setStatut($params["statut"]);
        $em->flush();

        return $relation;
    }

    private function insert_or_update_referentiel(
        EntityManagerInterface $em,
        array $params,
        string $entityClass
    ): ?ReferentielEntityInterface {
        $repo = $em->getRepository($entityClass);

        $code = $params["code"] ?? null;
        $libelle = $params["libelle"] ?? null;

        if (null === $code) {
            return null;
        }

        $obj = $repo->findOneBy(["code" => $code]);

        if (null === $obj) {
            $obj = new $entityClass();
            $obj->setCode($code);
        }
        $obj->setLibelle($libelle);
        $repo->save($obj, true);

        return $obj;
    }
}
