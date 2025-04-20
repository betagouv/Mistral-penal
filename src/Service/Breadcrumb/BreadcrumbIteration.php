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
namespace App\Service\Breadcrumb;

use Symfony\Component\Routing\RouterInterface;

class BreadcrumbIteration {
  private string $_label;
  private ?string $_url = null;
  private array $_translationParams = [];
  /**
   * @param string $label
   * @param string $pathName
   */
  public function __construct(string $label, ?string $pathName=null, array $options=[], array $translationParams=[], RouterInterface $router=null) {
    $this->_label = $label;
    $this->_translationParams = $translationParams;
    if(null !== $pathName)
      $this->_url = $router->generate($pathName, $options);
  }

  public function getUrl(): ?string { return $this->_url;}
  public function getLabel(): string { return $this->_label; }
  public function getTranslationParams(): array { return $this->_translationParams; }
}
