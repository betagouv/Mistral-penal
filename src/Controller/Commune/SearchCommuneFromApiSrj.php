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
namespace App\Controller\Commune;

use App\Entity\Commune;
use App\Repository\CommuneRepository;
use App\Service\RemoteWebService;
use App\Utils\Env;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class SearchCommuneFromApiSrj extends AbstractController {

  private ?RemoteWebService $_rws = null;
  private ?CommuneRepository $_cr = null;

  public function __construct(RemoteWebService $rws, CommuneRepository $cr)
  {
    $this->_rws = $rws;
    $this->_cr = $cr;
  }

  public function getCommuneRepository(): ?CommuneRepository
  {
    return $this->_cr;
  }

  public function getRemoteWebService(): ?RemoteWebService
  {
    return $this->_rws;
  }

  public function __invoke(Request $request): JsonResponse {
    $cr = $this->getCommuneRepository();
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?string $terms */
    $terms = $content['term']??$request->get('term',null);
    /** @var int $limit */
    $limit = $content['limit']??$request->get('limit',10);
    $remoteUrl = Env::get('MISTRAL_API_SRJ_SEARCH_COMMUNE');
    $method = Request::METHOD_GET;

    $result = $this
      ->getRemoteWebService()
      ->call(
        remoteUrl: $remoteUrl,
        method: $method,
        params: [
          'terms' => $terms,
          'limit' => $limit,
          'page' => 1
        ]
      )
    ;

    if(200 !== $result['statusCode'])
      return new JsonResponse(['errmsg' => "Un problème est survenu. La recherche est impossible"], 404);

    $tab=[];
    foreach($result['data']['communes'] as $commune) {
      $tab[]=[
        'id' => $commune['id'],
        'label' => $commune['libelle'].' ('.substr($commune['code_postal'],0,2).')',
        'value' => $commune['id'],
      ];
    }

    return new JsonResponse($tab);
  }
}
