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
namespace App\Service\Odt;

class Section {

  use ParserTrait;

  private string $_name;
  private ?string $_prototype=null;
  private ?string $_instance=null;

  public function __construct(string $name, string $xml) {
    $this->_name = $name;
    $matchBegin = preg_quote("{% block $name %}","/");
    $matchBegin = str_replace([" "],["[ ]*"], $matchBegin);
    $matchEnd = preg_quote("{% endblock $name %}","/");
    $matchEnd = str_replace([" "],["[ ]*"], $matchEnd);
    if(preg_match("/$matchBegin(.*)$matchEnd/i", $xml, $matches)) {
      $this->_prototype = $matches[0];
      $this->reloadInstanceFromPrototype();
    }
  }

  public function appendSection(Section $section, ?string $tagContainer=null): self
  {
    $name = $section->getName();
    $str = "{% render ".$section->getName()." %}";
    $posRenderBefore = strpos($this->getInstance(), $str);
    $len = strlen($str);
    ##############
    if(null !== $tagContainer) {
      $posRenderBefore = $this->findNearestTagFromPosition(
        xml: $this->getInstance(),
        tag: $tagContainer,
        position: $posRenderBefore
      );
      $posRenderAfter = $posRenderBefore;

      $complementaryPosition = null;
      $posInstanceBefore = $this->findNearestTagFromPosition(
        xml: $section->getInstance(),
        tag: $tagContainer,
        position: strlen($section->getInstance()),
        complementaryPosition: $complementaryPosition,
        isSection: true
      );

      $reduceInstance = substr(
        $section->getInstance(),
        $posInstanceBefore,
        $complementaryPosition - $posInstanceBefore
      );
      $section->setInstance($reduceInstance);
    }
    else {
      $posRenderAfter = $posRenderBefore + $len;
    }
    $xmlSplitBefore = substr($this->getInstance(), 0, $posRenderAfter);
    $xmlSplitAfter = substr($this->getInstance(), $posRenderAfter);
    $newXml = $xmlSplitBefore.$section->getInstance().$xmlSplitAfter;
    ##############
    $this->_instance = $newXml;
    $section->reset();
    return $this;
  }

  private function reloadInstanceFromPrototype(): void {
    $name       = $this->getName();
    $instance   = $this->_prototype;
    $matchBegin = preg_quote("{% block $name %}","/");
    $matchBegin = str_replace([" "],["[ ]*"], $matchBegin);
    $instance   = preg_replace("/$matchBegin/i","", $instance);
    $matchEnd   = preg_quote("{% endblock $name %}","/");
    $matchEnd   = str_replace([" "],["[ ]*"], $matchEnd);
    $instance   = preg_replace("/$matchEnd/i","", $instance);
    $this->_instance = $instance;
  }

  public function getName(): string {
    return $this->_name;
  }

  public function setVars(array $data, string $type=Odt::TYPE_PLAINTEXT): self {
    $this->_instance = self::insertVars($this->_instance, $data, $type);
    return $this;
  }

  public function setInstance(string $xml): self {
    $this->_instance = $xml;
    return $this;
  }
  public function getInstance(): ?string {
    return $this->_instance;
  }

  public function reset(): void {
    $this->reloadInstanceFromPrototype();
  }

  public function getPrototype(): string {
    return $this->_prototype;
  }

  public function isValid(): bool {
    return (null !== $this->_prototype);
  }
}
