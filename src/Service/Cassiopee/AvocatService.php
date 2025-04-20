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
namespace App\Service\Cassiopee;

use App\Utils\Env;
use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AvocatService extends AbstractCassiopeeService {

    private static function cleanText(string $text): string {
      return trim(str_replace(["\n"],[""],$text));
    }
    private static function cleanHtml(string $text): string {
      $html = preg_replace("/[ ]+/"," ",$text);
      $html = trim(str_replace(["\n"],["<br>"],$html));
      $html = preg_replace("/([>])[ ]+/","$1",$html);
      return $html;
    }

    /**
     * Récupération des avocats pour une page donnée
     * @param string $url Url à traiter
     * @param array $data Liste des informations récupérées
     * @return ?string $nextUrl La prochaine url à traiter
     */
    public static function getAvocatsByUrl(
      AvocatService $as,
      HttpBrowser $browser,
      string $url,
      array &$data=[]
    ): ?string
    {
      $page= explode("?",$url)[0];
      $crawler = $browser->request('POST', $url);

      $avocatNodes = $crawler
        ->filter('div[id=tbl-container-kcop] tr')
        ->reduce(function(Crawler $node, $i) {
          return ($node->filter('td')->count() === 5);
        })
      ;
      /** @var \DOMElement $avocatElement */
      foreach($avocatNodes as $avocatElement) {
        $tds = $avocatElement->getElementsByTagName('td');
        $idAvocat = preg_replace("/^(.*)identifierAvocat=(\d+)[,](.*)$/","$2",$tds[0]->getElementsByTagName('a')[0]->getAttribute('href'));
        $nom = self::cleanText($tds[0]->textContent);
        $prenom = self::cleanText($tds[1]->textContent);
        $adresse = self::cleanHtml($tds[2]->textContent);
        $barreau = self::cleanText($tds[3]->textContent);
        $etat = self::cleanText($tds[4]->textContent);

        $data[md5($nom.$prenom)]=["nom" => $nom,"prenom" => $prenom,"adresse" => $adresse,
          'barreau' => $barreau, 'etat' => $etat,"id" => $idAvocat
        ];
      }

      $url = $as->getNextPage($crawler, "listAvocat");
      $nextPage = $url ? Env::get('CASSIOPEE_BASE_URL').$url : null;
      return $nextPage;
    }

    public function getAvocats(): array {
      $browser = $this->getBrowser();
      $browser->followRedirects(false);

      $url = implode("", [
        Env::get('CASSIOPEE_BASE_URL'),
        Env::get('CASSIOPEE_AVOCAT')
      ]);

      $output=[];
      while(null!==($url = self::getAvocatsByUrl(as: $this, browser: $browser,url: $url,data: $output)
    )) { }
      return $output;
    }

}
