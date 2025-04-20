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
namespace App\Twig;

use Symfony\Component\Intl\Intl;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
  public function generateUrl(string $baseUrl, string $path): string {
    return preg_replace("/\/+$/", "", $baseUrl) . '/' . preg_replace("/^\/+/", "", $path);
  }
  public function rand(): string {
    $bytes = random_bytes(3);
    return  bin2hex($bytes);
  }

  public function getFilters(): array {
    return [
      new TwigFilter('datePlaintext', [AppRuntime::class, 'getDatePlaintext']),
      new TwigFilter('dateSmall', [AppRuntime::class, 'getDateSmall']),
      new TwigFilter('toDate', [AppRuntime::class, 'toDate']),
      new TwigFilter('translate', [AppRuntime::class,'translate']),
    ];
  }
  public function getFunctions(): array {
    return [
      new TwigFunction('last_day', [AppRuntime::class, 'getLastDay']),
      new TwigFunction('build_date', [AppRuntime::class, 'buildDate']),
      new TwigFunction('generate_url', [$this, 'generateUrl']),
      new TwigFunction('rand', [$this, 'rand']),
      new TwigFunction('month_plaintext', [AppRuntime::class, 'getMonthPlaintext']),
      new TwigFunction('make_diff_affaire_natinf', [AppRuntime::class, 'makeDiffAffaireNatinf'])
    ];
  }
}
