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
namespace App\Controller\Natinf;

use App\Entity\Natinf;
use App\Repository\NatinfRepository;
use App\Service\RemoteWebService;
use App\Utils\Env;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class SearchNatinfFromApiSrj extends AbstractController {

  private ?RemoteWebService $_rws = null;
  private ?NatinfRepository $_cr = null;

  public function __construct(RemoteWebService $rws, NatinfRepository $cr)
  {
    $this->_rws = $rws;
    $this->_cr = $cr;
  }

  public function getNatinfRepository(): ?NatinfRepository
  {
    return $this->_cr;
  }

  public function getRemoteWebService(): ?RemoteWebService
  {
    return $this->_rws;
  }

  #[Route('/api/natinfs/api-srj/v1', name: 'remote_api_srj_natinf_collection', methods: ['GET'], options: ["expose" => true])]
  public function __invoke(Request $request): JsonResponse {
    $cr = $this->getNatinfRepository();
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?string $terms */
    $terms = $content['term']??$request->get('term',null);
    /** @var ?string $strDateApplication */
    $strDateApplication = $content['date_application']??$request->get('date_application',null);
    /** @var \DateTime $dateApplication */
    $dateApplication = new \DateTime();
    if($strDateApplication) {
      if(preg_match("/(?<year>\d{4})[-](?<month>\d{2})[-](?<day>\d{2})/", $strDateApplication, $matches))
        $dateApplication = new \DateTime($matches['year'].'-'.$matches['month'].'-'.$matches['day']);
      else
        return new JsonResponse(['errmsg' => "Le format de la date d'application doit être YYYY-MM-DD"],404);
    }
    /** @var int $limit */
    $limit = $content['limit']??$request->get('limit',10);
    $remoteUrl = Env::get('MISTRAL_API_SRJ_SEARCH_NATINF');
    $method = Request::METHOD_GET;

    $result = $this
      ->getRemoteWebService()
      ->call(
        remoteUrl: $remoteUrl,
        method: $method,
        params: [
          'terms' => $terms,
          'limit' => $limit,
          'date_application' => $dateApplication->format('Y-m-d'),
          'page' => 1
        ]
      )
    ;

    if(200 !== $result['statusCode'])
      return new JsonResponse(['errmsg' => "Un problème est survenu. La recherche est impossible"], 404);

    $tab=[];

    foreach($result['data']['natinfs'] as $natinf) {
      $tab[]=[
        'id' => $natinf['id'],
        'label' => $natinf['code'].' '.$natinf['qualification'].' (V'.$natinf['version'].')',
        'value' => $natinf['id'],
      ];
    }

    return new JsonResponse($tab);
  }
}
