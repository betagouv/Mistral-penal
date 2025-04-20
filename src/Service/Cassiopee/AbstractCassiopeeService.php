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
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractCassiopeeService
{
    private ?HttpBrowser $browser = null;

    private ?string $CASSIOPEE_PROXY = null;
    private ?string $CASSIOPEE_DNS_PROXY = null;
    private array $CASSIOPEE_DNS_RESOLVE = [];
    private ?string $CASSIOPEE_INSECURE = null;
    private ?string $CASSIOPEE_BASE_URL = null;

    public function __construct(
        private ?RequestStack $requestStack,
        protected ?LoggerInterface $logger
    ) {
        $this->CASSIOPEE_DNS_PROXY = Env::get("CASSIOPEE_DNS_PROXY");
        $this->CASSIOPEE_PROXY = Env::get("CASSIOPEE_PROXY");
        $this->CASSIOPEE_INSECURE = boolval(Env::get("CASSIOPEE_INSECURE"));
        $this->CASSIOPEE_BASE_URL = Env::get("CASSIOPEE_BASE_URL");

        if (null === $this->CASSIOPEE_BASE_URL) {
            throw new \Exception(
                "Missing CASSIOPEE_BASE_URL environnement variable !"
            );
        }

        if (null !== $this->CASSIOPEE_DNS_PROXY) {
            $dnsProxyIp = gethostbyname($this->CASSIOPEE_DNS_PROXY);
            $cassiopeeHost = parse_url($this->CASSIOPEE_BASE_URL)["host"];

            $this->CASSIOPEE_DNS_RESOLVE = [$cassiopeeHost => $dnsProxyIp];
        }
    }

    private function getSession() {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException $e) {
            return null;
        }
    }

    private function createClient($headers = [])
    {
        return new RetryableHttpClient(HttpClient::create(
            [
                "proxy" => $this->CASSIOPEE_PROXY,
                "verify_peer" => !$this->CASSIOPEE_INSECURE,
                "verify_host" => !$this->CASSIOPEE_INSECURE,
                "headers" => $headers,
                "resolve" => $this->CASSIOPEE_DNS_RESOLVE
            ],
            5,
            0
        ), new CassiopeeRetryStrategy($this->logger), 5);
    }

    public function getBrowser(): HttpBrowser
    {
        if (null === $this->browser) {
            $this->browser = new HttpBrowser($this->createClient());
        }
        return $this->browser;
    }

    protected function createClientWithCookies()
    {
        if ($this->browser !== null) {
            $cookieJar = $this->browser->getCookieJar();
            $cookieHeader =
                "cookie: " .
                join(
                    "; ",
                    array_map(
                        fn($cookie): string => $cookie->__toString(),
                        $cookieJar->all()
                    )
                );
            return $this->createClient([$cookieHeader]);
        }
        return $this->createClient();
    }

    public function url(?string $uri = null): string
    {
        $url = $this->CASSIOPEE_BASE_URL;
        if (null !== $uri) {
            $url .= $uri;
        }
        return $url;
    }

    public function saveCookies()
    {
        $cookieJar = $this->getBrowser()->getCookieJar();

        $cookies = array_map(
            fn($cookie): string => $cookie->__toString(),
            $cookieJar->all()
        );

        $jsessionid = null;

        if ($this->getSession() !== null) {
            $this->getSession()->set("cassiopeeCookies", $cookies);
        }

        foreach ($cookieJar->all() as $cookie) {
            if ($cookie->getName() === "JSESSIONID") {
                $jsessionid = $cookie->getValue();
                break;
            }
        }

        return [
            "cookies" => $cookies,
            "jsessionid" => $jsessionid,
        ];
    }

    public static function getCookies($session)
    {
        return $session->get("cassiopeeCookies");
    }

    public function hasCookies(): bool
    {
        return $this->getSession() && self::getCookies($this->getSession());
    }

    public function restoreCookies(): void
    {
        if (!$this->getSession()) {
            return;
        }

        $cookiesData = self::getCookies($this->getSession());
        if (null === $cookiesData) {
            return;
        }

        $this->setCookies($cookiesData);
    }

    public function getCookiesInfo(): array
    {
        $output=[];
        $cookieJar = $this->getBrowser()->getCookieJar();
        foreach ($cookieJar->all() as $cookie) {
            $output[$cookie->getName()]=$cookie->getValue();
        }
        return $output;
    }

    public function setCookies(array $cookies): void
    {
        $cookieJar = $this->getBrowser()->getCookieJar();

        $cookieJar->clear();

        foreach ($cookies as $cookieStr) {
            try {
                $cookieJar->set(Cookie::fromString($cookieStr));
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }
    }

    protected function getNextPage($crawler, $paginationDivId)
    {
        $nextPage = null;

        $crawler
            ->filter("div#$paginationDivId .tile thead tr td[align=right] a")
            ->each(function ($navigationLink) use (&$nextPage) {
                if ($navigationLink->text() === ">") {
                    $nextPage = $navigationLink
                        ->getNode(0)
                        ->getAttribute("onclick");
                    preg_match(
                        "/^javascript:loadNextPage\('(?<action>.*)','\/cassiopee\/(?<url>.*)',(?<currentPage>[0-9]+),'.*'\);$/",
                        $nextPage,
                        $matches
                    );

                    if ($matches) {
                        $nextPage =
                            $matches["url"] .
                            (((int) $matches["currentPage"]) + 1) .
                            "&l=" .
                            $matches["action"];
                    }
                }
            });

        return $nextPage;
    }

    protected function browsePages(
        $browser,
        $crawler,
        $paginationDivId,
        $parseFunction
    ) {
        $nextPage = null;
        $currentCrawler = $crawler;
        $data = [];

        do {
            if ($nextPage !== null) {
                $currentCrawler = $browser->request(
                    "POST",
                    $this->url($nextPage)
                );
                if (\Fiber::getCurrent() !== null) {
                    \Fiber::suspend();
                }
            }
            $dataPage = $parseFunction($currentCrawler);
            $data = array_merge($data, $dataPage);

            $nextPage = $this->getNextPage($currentCrawler, $paginationDivId);
        } while ($nextPage !== null);

        return $data;
    }

    static function mapForm($crawler, $mapping, $data = null)
    {
        if ($data === null) {
            $data = [];
            $isDeletable = true;
        } else {
            $isDeletable = false;
        }

        $isEmpty = true;

        foreach ($mapping as $key => $value) {
            if (!is_string($value) && !array_is_list($value)) {
                if (
                    array_key_exists($key, $data) &&
                    $data[$key] !== null &&
                    is_array($data[$key])
                ) {
                    $data[$key] = self::mapForm($crawler, $value, $data[$key]);
                } else {
                    $data[$key] = self::mapForm($crawler, $value);
                }
            } else {
                if (is_string($value)) {
                    $value = [$value];
                }

                $data[$key] = null;

                foreach ($value as $v) {
                    $node = $crawler->filter(
                        "input[name=$v],select[name=$v],textarea[name=$v]"
                    );
                    if ($node->count() <= 0) {
                        continue;
                    }

                    if ($node->nodeName() === "select") {
                        $node = $node->filter("option:selected");
                        if (count($node) > 0) {
                            $code = self::trim($node->getNode(0)->getAttribute("value"));
                            $libelle = self::trim($node->text());
                            if (!empty($code)) {
                                $data[$key] = [
                                    "code" => $code,
                                    "libelle" => $libelle,
                                ];
                            }
                        }
                    } elseif (
                        $node->getNode(0)->getAttribute("type") === "checkbox"
                    ) {
                        $data[$key] = $node
                            ->getNode(0)
                            ->hasAttribute("checked");
                    } elseif ($node->nodeName() === "textarea") {
                        $data[$key] = self::trim($node->text());
                    } else {
                        $data[$key] = self::trim($node->getNode(0)->getAttribute("value"));
                    }

                    break;
                }
            }

            if ($isEmpty) {
                $isEmpty = empty($data[$key]);
            }
        }

        return $isDeletable && $isEmpty ? null : $data;
    }

    public static function trim($str) {
        return trim(
            urldecode(
                urlencode(
                    trim(str_replace("\xc2\xa0", " ", $str))
                )
            )
        );
    }
}
