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
namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RemoteWebService {

  private ?string $_remoteUrl=null;
  private array $_params=[];
  private ?string $_method=null;

  public function __construct(private RequestStack $requestStack) {
  }

  public function getSession(): SessionInterface {
    return $this->requestStack->getSession();
  }

  public function setToken(?string $token): self
  {
      $this->getSession()->set('token', $token);
      return $this;
  }

  public function getToken(): ?string
  {
      $token = $this->getSession()->get('token',null);
      return $token;
  }

  public function resetToken(): self
  {
      $this->getSession()->set('token', null);
      return $this;
  }

  public function hasToken(): bool
  {
      $token = $this->getSession()->get('token');
      return (null !== $token);
  }

  /**
   * Exécution d'un service web distant
   *
   * @param string $remoteUrl URL distante
   * @param string $method Méthode d'appel
   * @param array $params Liste des paramètres à passer lors de l'appel
   * @param bool $ignoreJWT Nécessité d'injecter le Bearer
   * @return array
   */
  public function call(
      string $remoteUrl,
      string $method=Request::METHOD_GET,
      array $params=[],
      bool $ignoreJWT=false
  ): array {
      $method = mb_strtoupper($method);
      /** @var $curl */
      $curl = curl_init($remoteUrl);
      curl_setopt($curl, CURLOPT_URL, $remoteUrl);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      /**
       * @author yanroussel
       *
       * On indique au site web de ne pas utiliser le proxy pour l'interrogation.
       */
      curl_setopt($curl, CURLOPT_PROXY, '');
      /** @var array $headers */
      $headers = [
          "Content-Type: application/json",
          "Accept: application/json",
      ];

      if(false === $ignoreJWT)
      {
          /** @var string|null $token */
          $token = $this->getToken();
          if(null !== $token) {
              $headers[]="auth: Bearer $token";
          }
      }

      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      switch($method) {
          case Request::METHOD_POST:
              curl_setopt($curl, CURLOPT_POST, true);
              curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
              break;
          case Request::METHOD_GET:
              $query = http_build_query($params);
              $remoteUrlWithoutParam = (explode("?", $remoteUrl))[0];
              curl_setopt($curl, CURLOPT_URL, "$remoteUrlWithoutParam?$query");
              break;
          default:
              throw new \Exception('method not implemented!');
      }

      /** @var array $data */
      $data     = [];
      $response = curl_exec($curl);
      /** @var int $statusCode */
      $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
      if(!curl_errno($curl)) {
          $infos = curl_getinfo($curl);
          $statusCode = (int)$infos['http_code'];
          try {
              $data = json_decode($response, true);
          }
          catch(\Exception $e) { }
      }
      curl_close($curl);

      return [
          'statusCode' => $statusCode,
          'data' => $data
      ];
  }
}
