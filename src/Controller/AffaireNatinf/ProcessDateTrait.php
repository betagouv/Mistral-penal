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
namespace App\Controller\AffaireNatinf;

use App\Entity\AffaireNatinf;
use App\Entity\HorodatageFait;
use App\Entity\OperateurHorodatage;
use Symfony\Component\HttpFoundation\Request;

trait ProcessDateTrait {
  private function processDate(AffaireNatinf $affaireNatinf, Request $request): void
  {
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?string $strDebutDate */
    $strDebutDate = $content['debut_date']??$request->get('debut_date',null);
    /** @var ?int $debutOperateur */
    $debutOperateurId = $content['debut_operateur']??$request->get('debut_operateur',null);
    /** @var string $strDebutHeure */
    $strDebutHeure = $content['debut_heure']??$request->get('debut_heure','0');
    /** @var string $strDebutMinute */
    $strDebutMinute = $content['debut_minute']??$request->get('debut_minute','0');
    /** @var string $strDebutHM */
    $strDebutHM = "1970-01-01 ".str_pad($strDebutHeure, 2, "0", STR_PAD_LEFT).':'.str_pad($strDebutMinute, 2, "0", STR_PAD_LEFT).':00';
    /** @var \DateTime $debutHeure */
    $debutHeure = new \DateTime($strDebutHM);
    /** @var ?\DateTime $debutDate */
    $debutDate = preg_match("/^(\d{4})[-](\d{2})[-](\d{2})$/", $strDebutDate) ? new \DateTime($strDebutDate) : null;
    /** @var ?string $strFinDate */
    $strFinDate = $content['fin_date']??$request->get('fin_date',null);
    /** @var ?int $finOperateurId */
    $finOperateurId = $content['fin_operateur']??$request->get('fin_operateur',null);
    /** @var string $strFinHeure */
    $strFinHeure = $content['fin_heure']??$request->get('fin_heure','0');
    /** @var string $strDebutMinute */
    $strFinMinute = $content['fin_minute']??$request->get('fin_minute','0');
    /** @var string $strFinHM */
    $strFinHM = "1970-01-01 ".str_pad($strFinHeure, 2, "0", STR_PAD_LEFT).':'.str_pad($strFinMinute, 2, "0", STR_PAD_LEFT).':00';
    /** @var \DateTime $finHeure */
    $finHeure = new \DateTime($strFinHM);
    /** @var ?\DateTime $finDate */
    $finDate = preg_match("/^(\d{4})[-](\d{2})[-](\d{2})$/", $strFinDate) ? new \DateTime($strFinDate) : null;
    /** @var HorodatageFait $newDebut */
    $newDebut = $affaireNatinf->getDebut();
    /** @var OperateurHorodatage $debutOperateur */
    $debutOperateur = $this
      ->getEntityManager()
      ->getRepository(OperateurHorodatage::class)
      ->find($debutOperateurId)
    ;
    $newDebut->setOperateur($debutOperateur);
    if(null !== $debutDate) {
      $newDebut->setDate($debutDate);
      $newDebut->setHeure($debutHeure);
    }

    /**
     * Date de fin
     *
     */
    /** @var ?HorodatageFait $newFin */
    $newFin = $affaireNatinf->getFin();
    if(null === $newFin) {
      $newFin = new HorodatageFait();
      $affaireNatinf->setFin($newFin);
      $this->getEntityManager()->persist($newFin);
    }
    if($finOperateurId) {
      /** @var OperateurHorodatage $finOperateur */
      $finOperateur = $this
        ->getEntityManager()
        ->getRepository(OperateurHorodatage::class)
        ->find($finOperateurId)
      ;
      $newFin->setOperateur($finOperateur);
    }

    if($finDate)
      $newFin->setDate($finDate);
    if($finHeure)
    $newFin->setHeure($finHeure);
  }
}
