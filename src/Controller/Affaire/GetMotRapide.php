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
namespace App\Controller\Affaire;

use App\Entity\Security\Account;
use App\Entity\Affaire;
use App\Repository\AffaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetMotRapide extends AbstractController {

  private ?AffaireRepository $_ar = null;
  private ?TranslatorInterface $_trans = null;

  public function __construct(
    AffaireRepository $ar,
    TranslatorInterface $trans
  ) {
    $this->_ar = $ar;
    $this->_trans = $trans;
  }

  public function getTranslator(): ?TranslatorInterface {
    return $this->_trans;
  }

  public function getAffaireRepository(): ?AffaireRepository {
    return $this->_ar;
  }

  public function __invoke(Affaire $affaire, Request $request) {
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?string $word */
    $word = $content['word']??$request->get('word',null);
    /** @var Account $account */
    $account = $this->getUser();
    /** @var array $params */
    $params = $this->getAffaireRepository()->getMotsRapides($affaire, $account, $word);
    return new JsonResponse($params);
  }
}
