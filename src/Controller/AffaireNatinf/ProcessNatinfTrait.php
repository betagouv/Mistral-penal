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
use App\Entity\Natinf;
use Symfony\Component\HttpFoundation\Request;

trait ProcessNatinfTrait {

  private function processNatinf(AffaireNatinf $affaireNatinf, Request $request): void
  {
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?integer $natinfId */
    $natinfId = $content['natinf_id']??$request->get('natinf_id',null);
    $natinf = $affaireNatinf->getNatinf();
    if(null !== $natinfId)
      $natinf = $this->getEntityManager()->getRepository(Natinf::class)->find($natinfId);
    $affaireNatinf->setNatinf($natinf);
    if(!$natinf&&(null !== $natinfId))
      throw new \Exception('Natinf non répertoriée');
  }

}
