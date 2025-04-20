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
namespace App\Service\Document;

use App\Entity\Affaire;
use App\Entity\AffairePersonne;
use App\Entity\Audience;
use App\Entity\RepresentantLegal;
use App\Entity\Structure;
use App\Service\Odt\Odt;
use App\Service\Odt\Section;
use App\Twig\AppRuntime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class DocumentRole
{
    const INFORMATION_MANQUANTE = '';
    private AppRuntime $runtime;
    private EntityManagerInterface $em;

    public function __construct(AppRuntime $runtime, EntityManagerInterface $em)
    {
        $this->runtime = $runtime;
        $this->em = $em;
    }

    public function create(Audience $audience, string $dir=''):string{
        /** @var string $trame */
        $trame = realpath("../docs/trames/roles/default.odt");
        /** @var ?string $tribunal */
        $tribunal = $audience->getService()->getTribunal();
        $juridiction = $audience->getService()->getJuridiction();
        /** @var AsciiSlugger $slugger */
        $slugger = new AsciiSlugger();
        $trameFilename = "Rôles - Audience #date# - #debut# - #service#";
        /** @var string $filename */

        if($dir == '') $dir = sys_get_temp_dir();

        $filename = $dir.'/'.$slugger->slug(
        str_replace([
            "#date#",
            "#debut#",
            "#service#",
            ],[
            $audience->getDate()->format("dmY"),
            str_replace(":",'h',$audience->getDebut()),
            $audience->getService()->getLabel(),
            ],
            $trameFilename
        )
        )->lower().'.odt';

        /** @var Odt $odt */
        $odt = new Odt($trame);
        $debut = $audience->getDebut();
        if($audience->getDebutAudience()) {
        $dateDebut = $audience->getDebutAudience();
        $dateDebut->setTimeZone(new \DateTimeZone('Europe/Paris'));
        $debut = $dateDebut->format('H:i');
        }
        $fin = '';
        if($audience->getFinAudience()) {
        $dateFin = $audience->getFinAudience();
        $dateFin->setTimeZone(new \DateTimeZone('Europe/Paris'));
        $fin = $dateFin->format('H:i');
        }
        $struct = $this
          ->em
          ->getRepository(Structure::class)
          ->findOneBy(['libelle' => $juridiction])
        ;
        $libelleCA = (null !== $struct) ? $struct->getCourAppel()->getLibelle() : "COUR_APPEL";

        $odt->setVars([
        'COUR_APPEL' => $libelleCA,
        'TRIBUNAL' => $tribunal,
        'service.libelle' => $audience->getService()->getLabel(),
        'audience.date' => $this->runtime->getDatePlaintext($audience->getDate()),
        'debut' => $debut,
        'fin' => $fin,
        'president' => $this->generateIntervenants($audience, Audience::ROLE_PRESIDENT),
        'greffe' => $this->generateIntervenants($audience, Audience::ROLE_GREFFE),
        'ministere' => $this->generateIntervenants($audience, Audience::ROLE_MINISTERE),
        'assesseur1' => $this->generateIntervenants($audience, Audience::ROLE_ASSESSEUR_1),
        'assesseur2' => $this->generateIntervenants($audience, Audience::ROLE_ASSESSEUR_2)
        ]);

        $this->manageSectionAffaire($odt, $this->runtime, $audience);

        $odt->save($filename);

        return $filename;
    }

    private function generateIntervenants(Audience $audience, string $role) {
        $intervenant = $audience->getIntervenantByRole($role);

        $remplacants = [];

        foreach ($audience->getAffaires() as $affaire) {
            $remplacant = $affaire->getIntervenantNomComplet($role);

            if (!empty($remplacant) && $remplacant !== $intervenant) {
                if (empty($remplacants[$remplacant])) {
                    $remplacants[$remplacant] = [];
                }

                $remplacants[$remplacant][] = $affaire->getNumeroDossier();
            }
        }

        $remplacantsStrs = [];

        foreach ($remplacants as $remplacant => $dossiers) {
            $remplacantsStrs[] =
                "remplacé(e) par "
                . $remplacant
                . " pour le(s) dossier(s) "
                . implode(", ", $dossiers);
        }

        return $intervenant . " " . implode("; ", $remplacantsStrs);
    }

    private function manageSectionRepresentantLegal(Odt $odt, Section $section, RepresentantLegal $rl): void {
      $sectionRL = $odt->getSection('rl');
      $lienSocial = $rl->getLienSocial();
      $lienJuridique = $rl->getLienJuridique();
      $representant = $rl->getRepresentant();
      $personne = $representant->getPersonne();
      $civilite = $personne->getCivilite();
      /** @var Commune $commune */
      $communeNaissance = $personne->getCommuneNaissance();
      $sectionRL->setVars([
        'rl.noaction' => '',
        'rl.lien_juridique' => $lienJuridique ? $lienJuridique->getLibelle() : '',
        'rl.lien_social' => $lienSocial ? $lienSocial->getLibelle() : '',
        'rl.civilite' => $civilite ? $civilite->getLibelle() : '',
        'rl.nom' => $personne->getNom(),
        'rl.prenom1' => $personne->getPrenom1(),
        'rl.naissance' => $personne->getDateNaissance() ? $personne->getDateNaissance()->format('d/m/Y') : 'XX/XX/XXXX',
        'rl.lieu_naissance' => $communeNaissance ? $communeNaissance->getPlaintext() : '',
        'rl.adresse' => $personne->getAdresse() ? $personne->getAdresse()->getAdresseComplete() : "",
        'rl.nationalite' => $personne->getNationalite() ? $personne->getNationalite()->getLibelleCourt() : "",
      ],Odt::TYPE_HTML);
      $section->appendSection($sectionRL);
    }

    private function manageSectionAffaire(Odt $odt, AppRuntime $runtime, Audience $audience): void {
        /** @var Collection<int, Affaire> $affaires */
        $affaires               = $audience->getAffaires();
        $section                = $odt->getSection('affaire');
        $sectionPrevenu         = $odt->getSection('prevenu');
        $sectionNatinf          = $odt->getSection('natinf');
        $sectionDecision        = $odt->getSection('decision_natinf_prevenu');
        if(true === $section->isValid()) {
          foreach($affaires as $index => $affaire) {
            /** @var Collection <int, AffairePersonne> $prevenus */
            $prevenus = $affaire->getPrevenus();
            /** @var Collection <int, AffairePersonne> $victimes */
            $victimes = $affaire->getVictimes();
            $section->setVars([
              'affaire.index' => $index+1,
              'numParquet' => substr($affaire->getNumeroParquet(),-11),
            ]);
            $isFirst=true;
            foreach($prevenus as $index => $affairePersonne) {
              $this->manageSectionPersonne($odt, $affairePersonne, $section, 'prevenu', $isFirst);
              $isFirst=false;
            }
            foreach($victimes as $affairePersonne)
              $this->manageSectionPersonne($odt, $affairePersonne, $section, 'victime', false);


            $odt->appendSection(section: $section, tagContainer: "table:table-row");
          }
        }
      }


    private function manageSectionPersonne(Odt $odt, AffairePersonne $affairePersonne, Section $section, string $trame='prevenu', $withNumber=false): void {
        $affaire                = $affairePersonne->getAffaire();
        /** @var Collection<int, AffairePersonne> $decisions */
        $decisions              = $affaire->getDecisions();

        // Le numéro de minute est commun à toutes les décisions.
        // Il n'y a pas de validation des données entrées par l'utilisateur pour l'instant, alors on itère sur les
        // décisions afin d'éviter de tomber sur une où l'utilisateur a oublié d'entrer la valeur.
        $numeroMinute = $withNumber
            ? array_reduce($decisions->toArray(), fn ($numero, $decision) => $numero ?? $decision->getNumeroMinute())
            : null;

        $sectionPrevenu         = $odt->getSection($trame);
        $sectionNatinf          = $odt->getSection('natinf');
        $sectionNatinf2          = $odt->getSection('natinf2');
        $sectionDecision        = $odt->getSection('decision_natinf_prevenu');
        $sectionDisqualRequal   = $odt->getSection('disqual_requal');
        $personne = $affairePersonne->getPersonne();
        $villeNaissance = $personne->getCommuneNaissance() ? $personne->getCommuneNaissance()->getLibelle() : '';
        $nomComplet = $personne->getNomComplet();

        $this->manageSectionRenvoi(
          odt: $odt,
          mainSection: $sectionPrevenu,
          affaire: $affaire,
          demandeur: $affairePersonne
        );
        $sectionPrevenu->setVars([
          'nComplet' => $nomComplet,
          'dNaiss' => $personne->getDateNaissance() ? $personne->getDateNaissance()->format('d/m/Y') : self::INFORMATION_MANQUANTE,
          'vNaiss' => $villeNaissance,
          'sigle' => ($withNumber && $numeroMinute) ? 'n°' : '',
          'number' => ($withNumber && $numeroMinute) ? $numeroMinute : '',
          'mPour' => $affairePersonne->getModePoursuite() ? $affairePersonne->getModePoursuite()->getCode() : '',
          'cPen' => $affairePersonne->getCategoriePenale() ? $affairePersonne->getCategoriePenale() : '',
          'modeComp' => $affairePersonne->getModeComparution() ? $affairePersonne->getModeComparution()->getLibelle() : self::INFORMATION_MANQUANTE,
          'nJug' => $affairePersonne->getNatureJugement() ? $affairePersonne->getNatureJugement() : '',
          'personne.avocat' => $affairePersonne->getAvocat() ?? self::INFORMATION_MANQUANTE,
        ]);
        foreach($affairePersonne->getRepresentants() as $link) {
          $this->manageSectionRepresentantLegal(odt: $odt, section: $sectionPrevenu,rl: $link);
        }

        /***
         * nature d'infraction
         *
         */
        foreach($affairePersonne->getAffaireNatinfs() as $affaireNatinf) {
         if(!$personne->isDisqualifie($affaireNatinf)) {
           /**
            * @author yanroussel
            *         On affiche uniquement l'infraction primitive
            */
           $affaireNatinf = $affaireNatinf->getDuplicateRoot()??$affaireNatinf;
           $debut = $affaireNatinf->getDebut() ? $affaireNatinf->getDebut()->getPlaintext() : self::INFORMATION_MANQUANTE;
           $fin = $affaireNatinf->getFin() ? $affaireNatinf->getFin()->getPlaintext() : '';
           $natinf = $affaireNatinf->getNatinf();
           $sectionNatinf->setVars([
             'natinf.date.debut' => $debut,
             'natinf.date.fin' => $fin,
             'natinf.code' => $natinf->getCode(),
             'natinf.libelle' => $natinf->getLibelle(),
             'natinf.lieu' => $affaireNatinf->getLocalizationPlaintext(),
           ]);
           $sectionPrevenu->appendSection($sectionNatinf);
         }

        }
        /**
         * \nature d'infraction
         */

        /**
         * Affichage des disqual/requal
         *
         */
        foreach($affairePersonne->getActiveAffaireNatinfs() as $activeAffaireNatinf)
        {
          $originAffaireNatinf = $activeAffaireNatinf->getDuplicateRoot();
          if(null === $originAffaireNatinf)
            continue;
          $debut = $originAffaireNatinf->getDebut() ? $originAffaireNatinf->getDebut()->getPlaintext() : self::INFORMATION_MANQUANTE;
          $fin = $originAffaireNatinf->getFin() ? $originAffaireNatinf->getFin()->getPlaintext() : '';
          $natinf = $originAffaireNatinf->getNatinf();
          $sectionNatinf->setVars([
            'natinf.date.debut' => $debut,
            'natinf.date.fin' => $fin,
            'natinf.code' => $natinf->getCode(),
            'natinf.libelle' => $natinf->getLibelle(),
            'natinf.lieu' => $originAffaireNatinf->getLocalizationPlaintext(),
          ]);
          $debut = $activeAffaireNatinf->getDebut() ? $activeAffaireNatinf->getDebut()->getPlaintext() : self::INFORMATION_MANQUANTE;
          $fin = $activeAffaireNatinf->getFin() ? $activeAffaireNatinf->getFin()->getPlaintext() : '';
          $natinf = $activeAffaireNatinf->getNatinf();
          $sectionNatinf2->setVars([
            'natinf.date.debut' => $debut,
            'natinf.date.fin' => $fin,
            'natinf.code' => $natinf->getCode(),
            'natinf.libelle' => $natinf->getLibelle(),
            'natinf.lieu' => $activeAffaireNatinf->getLocalizationPlaintext(),
          ]);
          $sectionDisqualRequal->setVars([
            'disqual_requal.type' => ($activeAffaireNatinf->isDisqualRequal() ? 'DISQUALIFICATION/REQUALIFICATION' : 'MODIFICATION DE LA QUALIFICATION INITIALE'),
          ]);
          $sectionDisqualRequal->appendSection($sectionNatinf);
          $sectionDisqualRequal->appendSection($sectionNatinf2);
          $sectionPrevenu->appendSection($sectionDisqualRequal);
        }

        /**
         * décisions
         */
        foreach($decisions as $decision)
        {
         if($decision->getAffairePersonne() == $affairePersonne) {
           $sectionDecision->setVars([
             'decision.prevention' => $decision->getDecisionPrevention() ? mb_strtoupper($decision->getDecisionPrevention()->getLibelle()) : self::INFORMATION_MANQUANTE,
             'decision.sanction' => $decision->getDecisionSanction() ? mb_strtoupper($decision->getDecisionSanction()->getLibelle()) : self::INFORMATION_MANQUANTE,
             'decision.modalitePeine' => $decision->getModulationPeine() ? ' / '.mb_strtoupper($decision->getModulationPeine()->getLibelle()) : '',
           ]);
           /** @var int $nbActiveNatinfs */
           $nbActiveNatinfs = $affairePersonne->getActiveAffaireNatinfs()->count();
           /** @var int $nbCurrentNatinfs */
           $nbCurrentNatinfs = $decision->getAffaireNatinfs()->count();
           $decisionComment = '';

           if($nbActiveNatinfs > $nbCurrentNatinfs) {
             $decisionComment = 'Sur les infractions suivantes :';
             foreach($decision->getAffaireNatinfs() as $affaireNatinf) {
               $debut = $affaireNatinf->getDebut() ? $affaireNatinf->getDebut()->getPlaintext() : self::INFORMATION_MANQUANTE;
               $fin = $affaireNatinf->getFin() ? $affaireNatinf->getFin()->getPlaintext() : '';
               $natinf = $affaireNatinf->getNatinf();

               $sectionNatinf->setVars([
                 'natinf.date.debut' => $debut,
                 'natinf.date.fin' => $fin,
                 'natinf.code' => $natinf->getCode(),
                 'natinf.libelle' => $natinf->getLibelle(),
                 'natinf.lieu' => $affaireNatinf->getLocalizationPlaintext(),
               ]);
               $sectionDecision->appendSection($sectionNatinf);
             }
           }

           $sectionDecision->setVars([
             'decision.comment' => $decisionComment,
             'decision.peines' => $decision->getPeines(),
           ], Odt::TYPE_HTML_WITH_RN);
           $sectionPrevenu->appendSection(section: $sectionDecision, tagContainer: 'text:p');
         }
        }
        /**
         * \décisions
         */
        $section->appendSection(section: $sectionPrevenu, tagContainer: "table:table-row");
      }

      private function manageSectionRenvoi(Odt $odt, Section $mainSection, Affaire $affaire, AffairePersonne $demandeur): void {
        /** @var Collection<int, Renvoi> $renvois */
        $renvois = $affaire->getRenvois();
        $section = $odt->getSection('decision_renvoi');
        if(true === $section->isValid()) {
          foreach($renvois as $renvoi) {
            if($renvoi->hasAffairePersonne($demandeur)) {
              $section->setVars([
                'date' => $renvoi->getDate() ? $renvoi->getDate()->format('d/m/Y') : '',
                'heure' => $renvoi->getDate() ? mb_strtoupper('à '.$renvoi->getDate()->format('H:i')) : '',
                'motif' => $renvoi->getRenvoiMotif() ? mb_strtoupper($renvoi->getRenvoiMotif()->getLibelle()) : '',
                'mesure_surete' => $renvoi->getMesureSurete() ? 'avec '. $renvoi->getMesureSurete()->getLibelle() : '',
                'details_mesure_surete' => $renvoi->getDetailsMesureSurete(),
                'expertise' => $renvoi->getExpertise() ?: '—',
              ]);

              $mainSection->appendSection($section, 'text:p');
            }
          }
        }
      }
}
