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
use App\Entity\NatinfVersion;
use App\Entity\Nataff;
use App\Repository\NataffRepository;
use App\Repository\NatinfRepository;
use App\Repository\NatinfVersionRepository;
use App\Service\RemoteWebService;
use App\Utils\Env;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class GetNatinfFromApiSrj extends AbstractController {

  private ?RemoteWebService $_rws = null;
  private ?NataffRepository $_nar = null;
  private ?NatinfRepository $_cr = null;
  private ?NatinfVersionRepository $_nvr = null;

  public function __construct(
    RemoteWebService $rws,
    NatinfRepository $cr,
    NatinfVersionRepository $nvr,
    NataffRepository $nar
  )
  {
    $this->_rws = $rws;
    $this->_cr = $cr;
    $this->_nvr = $nvr;
    $this->_nar = $nar;
  }

  public function getNataffRepository(): ?NataffRepository
  {
    return $this->_nar;
  }

  public function getNatinfVersionRepository(): ?NatinfVersionRepository
  {
    return $this->_nvr;
  }

  public function getNatinfRepository(): ?NatinfRepository
  {
    return $this->_cr;
  }

  public function getRemoteWebService(): ?RemoteWebService
  {
    return $this->_rws;
  }

  #[Route('/api/natinfs/api-srj/v1/{id}', name: 'remote_api_srj_natinf_details', methods: ['GET'], options: ["expose" => true])]
  public function __invoke(Request $request): JsonResponse {
    $cr = $this->getNatinfRepository();
    /** @var array $content */
    $content = json_decode($request->getContent(), true);
    /** @var ?int $id */
    $id = $content['id']??$request->get('id',null);
    $remoteUrl = str_replace(["{id}"],[$id], Env::get('MISTRAL_API_SRJ_GET_NATINF'));
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

    $code = $result['data']['code'];
    $libelle = $result['data']['qualification'];

    $nataffCode = $result['data']['nataff']['code'] ?? null;
    $nataffLibelle = $result['data']['nataff']['libelle'] ?? null;
    /** @var ?Nataff $nataff */
    $nataff = null;
    if(null !== $nataffCode) {
      $nataff = $this->getNataffRepository()->findOneBy(['code' => $nataffCode]);
      if(null === $nataff) {
        $nataff = new Nataff();
        $nataff->setCode($nataffCode);
        $nataff->setLibelle($nataffLibelle);
        $this->getNataffRepository()->save($nataff, true);
      }
    }

    /** @var ?Natinf $natinf */
    $natinf = $this->getNatinfRepository()->findOneBy(['code' => $code]);
    if(null === $natinf) {
      $natinf = new Natinf();
      $natinf->setCode($code);
      $natinf->setLibelle($libelle);
    }

    if($nataff)
      $natinf->setNataff($nataff);

    $cr->save($natinf, true);

    $version = $result['data']['version'];
    $dateDebutApplication = $result['data']['dateDebutApplication'] ? new \DateTime($result['data']['dateDebutApplication']) : null;
    $dateFinApplication = $result['data']['dateFinApplication'] ? new \DateTime($result['data']['dateFinApplication']) : null;
    $natinfVersion = $this->getNatinfVersionRepository()->findOneBy(['natinf' => $natinf, 'version' => $version]);
    if(null === $natinfVersion) {
      $natinfVersion = new NatinfVersion();
      $natinfVersion->setNatinf($natinf);
      $natinfVersion->setQualification($libelle);
      $natinfVersion->setVersion($version);
      $natinfVersion->setDateApplication($dateDebutApplication);
      $natinfVersion->setDateFinApplication($dateFinApplication);
      $natinfVersion->setNatinf($natinf);
      $this->getNatinfVersionRepository()->save($natinfVersion, true);
    }

    return new JsonResponse([
      'id' => $natinf->getId(),
      'code' => $natinf->getCode(),
      'libelle' => $natinf->getLibelle(),
      'version' => $natinfVersion->getVersion(),
    ]);
  }
}
