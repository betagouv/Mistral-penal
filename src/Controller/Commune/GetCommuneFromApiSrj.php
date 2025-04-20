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
class GetCommuneFromApiSrj extends AbstractController {

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
    /** @var ?int $id */
    $id = $content['id']??$request->get('id',null);
    $remoteUrl = str_replace(["{id}"],[$id], Env::get('MISTRAL_API_SRJ_GET_COMMUNE'));
    $method = Request::METHOD_GET;

    $result = $this
      ->getRemoteWebService()
      ->call(
        remoteUrl: $remoteUrl,
        method: $method,
        params: []
      )
    ;

    if(200 !== $result['statusCode'])
      return new JsonResponse(['errmsg' => "L'identifiant n'est pas valide"], 404);

    $code = $result['data']['codeInsee'];
    $libelle = $result['data']['libelle'];
    $codePostal = $result['data']['codePostal'];
    $commune = $this->getCommuneRepository()->findOneBy(['code' => $code]);
    if(null === $commune) {
      $commune = new Commune();
      $commune->setCode($code);
    }
    $commune->setLibelle($libelle);
    $commune->setCodePostal($codePostal);
    $cr->save($commune, true);


    return new JsonResponse([
      'id' => $commune->getId(),
      'code' => $commune->getCode(),
      'libelle' => $commune->getLibelle(),
      'codePostal' => $commune->getCodePostal(),
    ]);
  }
}
