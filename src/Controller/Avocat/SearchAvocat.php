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
namespace App\Controller\Avocat;

use App\Entity\Avocat;
use App\Repository\AvocatRepository;
use App\Utils\Env;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class SearchAvocat extends AbstractController {

  public function __construct(
    private AvocatRepository $avocatRepository
  ) { }

  #[Route('/api/avocats-search', name: 'avocat_collection_search', methods: ['GET'], options: ["expose" => true])]
  public function __invoke(Request $request): JsonResponse {
    $ar = $this->avocatRepository;
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?string $terms */
    $terms = $content['term']??$request->get('term',null);
    $terms = str_replace("\xc2\xa0", '', $terms);
    /** @var int $limit */
    $limit = $content['limit']??$request->get('limit',10);
    /** @var int $quickDisplay */
    $quickDisplay = $content['quick_display']??$request->get('quick_display', 1);

    $avocats = $ar->findAllByTerm(terms: $terms,limit: $limit);
    $output=[];
    if(1===$quickDisplay) {
      foreach($avocats['avocats'] as $avocat)
        $output[]=[
          'label' => ucfirst($avocat['prenom']).' '.strtoupper($avocat['nom']),
          'value' => ucfirst($avocat['prenom']).' '.strtoupper($avocat['nom']),
          'desc' => ucfirst($avocat['prenom']).' '.strtoupper($avocat['nom']).' ('.strtoupper($avocat['barreau']).')',
          'id' => $avocat['id'],
        ];
    }
    else
      $output=$avocats;
    return new JsonResponse($output);
  }
}
