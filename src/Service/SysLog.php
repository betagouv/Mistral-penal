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

use App\Contracts\SysLogInterface;
use App\Utils\Env;
use Psr\Log\LoggerInterface;

class SysLog implements SysLogInterface {
  private ?LoggerInterface $_logger=null;
  private ?string $_action=null;
  private array $_garbage=[];

  public function getAction(): ?string {
    return $this->_action;
  }

  public function setAction(string $action): self {
    $this->_action=$action;

    return $this;
  }

  public function getGarbage(): array {
    return $this->_garbage;
  }

  public function append(string $item, mixed $value): self {
    $this->_garbage[$item]=$value;

    return $this;
  }

  public function flush(): void {
    $this->info($this->getAction(), $this->getGarbage());
    $this->_action = null;
    $this->_garbage = [];
  }

  public function getLogger(): ?LoggerInterface {
    return $this->_logger;
  }

  public function __construct(LoggerInterface $logger) {
    $this->_logger = $logger;
  }

  public function info(string $action, array $params=[]): self {
    $this
      ->getLogger()
      ->info($action, $params);
    return $this;
  }
}
