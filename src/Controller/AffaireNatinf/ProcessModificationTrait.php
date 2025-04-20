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
use Symfony\Component\HttpFoundation\Request;

trait ProcessModificationTrait {

  public function processModification(AffaireNatinf $affaireNatinf, Request $request): void
  {
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?string $lieu */
    $lieu = $content['lieu']??$request->get('lieu',null);
    /** @var string $qualificationDeveloppee */
    $qualificationDeveloppee = $content['qualification_developpee']??$request->get('qualification_developpee',null);
    $affaireNatinf
      ->setLieu($lieu)
      ->setQualificationDeveloppee($qualificationDeveloppee)
    ;
  }

}
