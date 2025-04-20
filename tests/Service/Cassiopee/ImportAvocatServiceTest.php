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
namespace App\Test\Service\Cassiopee;
use App\Service\Cassiopee\AvocatService;
use App\Tests\KernelTestCase;

class ImportAvocatServiceTest extends KernelTestCase {

    public function testGetAvocats(): void {

      self::bootKernel();
      $container = static::getContainer();
      $avocatService = $container->get(AvocatService::class);
      $this->section("Test sur l'import des avocats");

      $login = "corinne.parent106";
      $password = "justice1";
      $idCassiopee = "corinne.parent106";
      $avocats=[];
      $this
        ->given(
          description: "Je souhaite récupérer les avocats pour une juridiction donnée",
          avocatService: $avocatService,
          login: $login,
          password: $password,
          avocats: $avocats
        )
        ->when(
          description: "Je me connecte en tant que $login/$password",
          callback: function(
            AvocatService $avocatService,
            string $login,
            string $password,
            &$loginInfos=[]
          ) {
            $loginInfos = $avocatService->login(login: $login, password: $password);
          }
        )
        ->then(
          description: "Je retrouve l'identifiant Cassiopée associé $idCassiopee",
          callback: function(array $loginInfos) {
            return $loginInfos['user']['idCassiopee']??null;
          },
          result: $idCassiopee
        )
        ->andWhen(
          description: "J'accède à la page des avocats",
          callback: function(AvocatService $avocatService, array &$avocats=[]) {
            $avocats = $avocatService->getAvocats();
          }
        )
        ->andThen(
          description: "Je dois trouver plus de dix avocats",
          callback: function(array $avocats) {
            return count($avocats)>10;
          },
          result: true
        )
      ;
    }
}
