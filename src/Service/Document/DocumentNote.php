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

use App\Entity\Structure;
use App\Entity\Audience;
use App\Entity\Affaire;
use App\Entity\AffairePersonne;
use App\Entity\RepresentantLegal;
use App\Service\Odt\Odt;
use App\Service\Odt\Section;
use App\Twig\AppRuntime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class DocumentNote
{
    const INFORMATION_MANQUANTE = '';
    private AppRuntime $runtime;
    private EntityManagerInterface $em;

    public function __construct(AppRuntime $runtime, EntityManagerInterface $em)
    {
        $this->runtime = $runtime;
        $this->em = $em;
    }

    public function create(Affaire $affaire, string $dir=''):string{
         /** @var string $trame */
        $trame = realpath("../docs/trames/note_audience/default.odt");
        /** @var Audience $audience */
        $audience = $affaire->getAudience();
        /** @var ?string $tribunal */
        $tribunal = $audience->getService()->getTribunal();
        $juridiction = $audience->getService()->getJuridiction();
        /** @var AsciiSlugger $slugger */
        $slugger = new AsciiSlugger();
        $trameFilename = "Note-Dossier#reference#-Audience#date#";
        /** @var string $filename */
        if($dir == '') $dir = sys_get_temp_dir();
        $filename = $dir.'/'.$slugger->slug(
            str_replace([
                "#reference#",
                "#date#",
            ],[
                $affaire->getNumeroDossier(),
                $audience->getDate()->format("dmY"),
            ],
            $trameFilename
            )
        )->lower().'.odt';
        /** @var Odt $odt */
        $odt = new Odt($trame);
        $debut = $audience->getDebut();
        if($audience->getDebutAudience()){
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
            'numeroParquet' => substr($affaire->getNumeroParquet(),-11),
            'president' => $affaire->getPresident(),
            'greffe' => $affaire->getGreffier(),
            'ministere' => $affaire->getMinisterePublic(),
            'assesseur1' => $affaire->getAssesseur1(),
            'assesseur2' => $affaire->getAssesseur2(),
            'scelle' => $affaire->isScelle() ? 'OUI' : 'NON',
        ]);

        $this->manageSectionMisEnCause($odt, $this->runtime, $affaire);
        $this->manageSectionVictime($odt, $this->runtime, $affaire);
        $this->manageSectionDecisionPrevenu($odt, $this->runtime, $affaire);

        /**
         * @author yanroussel
         * @description Injection de la note d'audience
         */
        $odt->setVars([
            'affaire.noteAudience' => $affaire->getLastNoteAudience() ? Odt::convertHtmlToODT($affaire->getLastNoteAudience()->getNote()) : '',
        ],Odt::TYPE_HTML);

        $odt->save($filename);

        return $filename;
    }

    private static function generateNatureJugement(AffairePersonne $affairePersonne): string
    {
        $puceKo = "□";
        $puceOk = "■";
        $natureJugement = $affairePersonne->getNatureJugement();
        $cases = ['C', 'CAS', 'D', 'ID'];
        $html = "";
        foreach($cases as $case) {
            $puce = ($case == $natureJugement) ? $puceOk : $puceKo;
            $html.= " $puce ".str_pad($case, 40, " ", STR_PAD_LEFT);
        }
        return $html;
    }

    private function manageSectionRenvoi(Odt $odt, Section $mainSection, AppRuntime $runtime, Affaire $affaire, AffairePersonne $demandeur): void {
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

    private function manageSectionDecisionPrevenu(Odt $odt, AppRuntime $runtime, Affaire $affaire): void {
        /** @var Collection<int, Decision> $decisions */
        $decisions = $affaire->getDecisions();
        $section = $odt->getSection('decision_prevenu');
        $sectionDecision = $odt->getSection('decision_natinf_prevenu');
        $sectionNatinf = $odt->getSection('natinf');
        if(true === $section->isValid()) {
            foreach($affaire->getVictimes() as $affairePersonne) {
                $this->manageSectionRenvoi(
                    odt: $odt,
                    mainSection: $section,
                    runtime: $runtime,
                    affaire: $affaire,
                    demandeur: $affairePersonne
                );
                $section->setVars([
                    'personne.nomComplet' => trim($affairePersonne->getPersonne()->getNomComplet()) ?: self::INFORMATION_MANQUANTE,
                ]);
                $odt->appendSection($section);
            }

            foreach($affaire->getPrevenus() as $affairePersonne) {
            $this->manageSectionRenvoi(
                odt: $odt,
                mainSection: $section,
                runtime: $runtime,
                affaire: $affaire,
                demandeur: $affairePersonne
            );

            $personne = $affairePersonne->getPersonne();
            $villeNaissance = $personne->getCommuneNaissance() ? $personne->getCommuneNaissance()->getLibelle() : '';
            $nomComplet = $personne->getNomComplet();
            foreach($decisions as $decision)
            {
                if($decision->getAffairePersonne() == $affairePersonne) {

                $sectionDecision->setVars([
                    'decision.prevention' => $decision->getDecisionPrevention() ? mb_strtoupper($decision->getDecisionPrevention()->getLibelle()) : self::INFORMATION_MANQUANTE,
                    'decision.sanction' => $decision->getDecisionSanction() ? mb_strtoupper($decision->getDecisionSanction()->getLibelle()) : self::INFORMATION_MANQUANTE,
                    'decision.modalitePeine' => $decision->getModulationPeine() ? ' / '.mb_strtoupper($decision->getModulationPeine()->getLibelle()) : '',
                ]);

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
                    if( $affaire->getActiveAffaireNatinfs()->count() > $affairePersonne->getActiveAffaireNatinfs()->count() ) {
                    $sectionDecision->setVars([
                        'display.nature_infraction' => 'Sur les infractions suivantes :'
                    ]);
                    $sectionDecision->appendSection($sectionNatinf);
                    }
                    else {
                    $sectionDecision->setVars([
                        'display.nature_infraction' => ''
                    ]);
                    }
                }

                $sectionDecision->setVars([
                    'decision.peines' => $decision->getPeines(),
                ], Odt::TYPE_HTML_WITH_RN);
                $section->appendSection($sectionDecision);
                }
            }
            $section->setVars([
                'personne.nomComplet' => trim($nomComplet) ?: self::INFORMATION_MANQUANTE,
            ]);

            $odt->appendSection($section);
            }
        }
    }

    private function manageSectionVictime(Odt $odt, AppRuntime $runtime, Affaire $affaire): void {
        $section = $odt->getSection('victime');
        if(true === $section->isValid()) {
            foreach($affaire->getVictimes() as $affairePersonne) {
                /** @var ?Personne $personne */
                $personne = $affairePersonne->getPersonne();
                $villeNaissance = ($personne && $personne->getCommuneNaissance()) ? $personne->getCommuneNaissance()->getLibelle() : '';
                $nomComplet = $personne ? $personne->getNomComplet() : self::INFORMATION_MANQUANTE;
                $natureJugement = self::generateNatureJugement($affairePersonne);
                $section->setVars([
                'personne.statut' => $affairePersonne->getStatut() ? $affairePersonne->getStatut()->getLibelle() : "Victime",
                'personne.nomComplet' => trim($nomComplet) ?: self::INFORMATION_MANQUANTE,
                'personne.dateNaissance' => $runtime->getDatePlaintext($personne->getDateNaissance()),
                'personne.villeNaissance' => trim($villeNaissance) ?: self::INFORMATION_MANQUANTE,
                'personne.adresseComplete' => $personne->getAdresse() ? $personne->getAdresse()->getAdresseComplete() : self::INFORMATION_MANQUANTE,
                'personne.situationFamilliale' => $personne->getSituationFamilliale() ? $personne->getSituationFamilliale()->getLibelle() : self::INFORMATION_MANQUANTE,
                'personne.modeConvocation' => $affairePersonne->getModeConvocation() ? $affairePersonne->getModeConvocation()->getLibelle() : self::INFORMATION_MANQUANTE,
                'personne.dateConvocation' => $affairePersonne->getDateConvocation() ? $affairePersonne->getDateConvocation()->format('d/m/Y') : '',
                'personne.modeComparution' => $affairePersonne->getModeComparution() ? $affairePersonne->getModeComparution()->getLibelle() : self::INFORMATION_MANQUANTE,
                'personne.avocat' => $affairePersonne->getAvocat() ?? self::INFORMATION_MANQUANTE,
                'personne.assisteDe' => $affairePersonne->getAssisteDe() ?? self::INFORMATION_MANQUANTE,
                'personne.representePar' => $affairePersonne->getAvocat() ?? self::INFORMATION_MANQUANTE,
                'personne.natureJugement' => $natureJugement,
                ]);
                foreach($affairePersonne->getRepresentants() as $link) {
                  $this->manageSectionRepresentantLegal(odt: $odt, section: $section,rl: $link);
                }

                $odt->appendSection($section);
            }
        }
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
    /**
     * Gestion de la section "mis_en_cause"
     *
     * @param Odt $odt
     * @param AppRuntime $runtime
     * @param Affaire $affaire
     * @return void
     */
    private function manageSectionMisEnCause(Odt $odt, AppRuntime $runtime, Affaire $affaire): void {
      $section = $odt->getSection('mis_en_cause');
      $sectionNatinf = $odt->getSection('natinf');

      if(true === $section->isValid()) {
        foreach($affaire->getPrevenus() as $affairePersonne) {
          $personne = $affairePersonne->getPersonne();
          $villeNaissance = $personne->getCommuneNaissance() ? $personne->getCommuneNaissance()->getLibelle() : '';
          $nomComplet = $personne->getNomComplet();
          $natureJugement = self::generateNatureJugement($affairePersonne);
          $section->setVars([
            'personne.nomComplet' => trim($nomComplet) ?: self::INFORMATION_MANQUANTE,
            'personne.dateNaissance' => $runtime->getDatePlaintext($personne->getDateNaissance()),
            'personne.villeNaissance' => trim($villeNaissance) ?: self::INFORMATION_MANQUANTE,
            'personne.parent.mere' => $personne->getMere()?->getNomComplet() ?? self::INFORMATION_MANQUANTE,
            'personne.parent.pere' => $personne->getPere()?->getNomComplet() ?? self::INFORMATION_MANQUANTE,
            'personne.adresseComplete' => $personne->getAdresse()->getAdresseComplete() ?? self::INFORMATION_MANQUANTE,
            'personne.situationFamilliale' => $personne->getSituationFamilliale() ? $personne->getSituationFamilliale()->getLibelle() : self::INFORMATION_MANQUANTE,
            'personne.profession' => $personne->getProfession(),
            'personne.nationalite' => $personne->getNationalite() ? $personne->getNationalite()->getLibelle() : self::INFORMATION_MANQUANTE,
            'personne.antecedentsJudiciaires' => $personne->getAntecedentJudiciaire() ? $personne->getAntecedentJudiciaire()->getLibelle() : self::INFORMATION_MANQUANTE,
            'personne.categoriePenale' => $personne->getCategoriePenale() ? $personne->getCategoriePenale()->getLibelle() : self::INFORMATION_MANQUANTE,
            'personne.avocat' => $affairePersonne->getAvocat() ?? self::INFORMATION_MANQUANTE,
            'personne.modeConvocation' => $affairePersonne->getModeConvocation() ? $affairePersonne->getModeConvocation()->getLibelle() : self::INFORMATION_MANQUANTE,
            'personne.dateConvocation' => $affairePersonne->getDateConvocation() ? $affairePersonne->getDateConvocation()->format('d/m/Y') : '',
            'personne.modeComparution' => $affairePersonne->getModeComparution() ? $affairePersonne->getModeComparution()->getLibelle() : self::INFORMATION_MANQUANTE,
            'personne.assisteDe' => $affairePersonne->getAssisteDe() ?? self::INFORMATION_MANQUANTE,
            'personne.representePar' => $affairePersonne->getAvocat() ?? self::INFORMATION_MANQUANTE,
            'personne.natureJugement' => $natureJugement,
            'personne.modePoursuite' => $affairePersonne->getModePoursuite() ? $affairePersonne->getModePoursuite()->getLibelle() : self::INFORMATION_MANQUANTE,
          ]);

          foreach($affairePersonne->getRepresentants() as $link) {
            $this->manageSectionRepresentantLegal(odt: $odt, section: $section,rl: $link);
          }
          foreach($affairePersonne->getActiveAffaireNatinfs() as $affaireNatinf) {
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
            $section->appendSection($sectionNatinf);
          }

          $odt->appendSection($section);
        }
      }
    }
}
