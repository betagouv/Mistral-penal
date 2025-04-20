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
namespace App\Tests;

class TestGiven {

  const PREFIX_WHEN = "[SI]";
  const PREFIX_AND_WHEN = "[ET SI]";
  const PREFIX_THEN = "[ALORS]";
  const PREFIX_AND_THEN = "[ET ALORS]";

  private array $_args = [];
  private ?KernelTestCase $_testCase = null;
  private ?TestMessageError $_err = null;

  public function __construct(KernelTestCase $testCase, array $args) {
      $this->_args = array_merge($args, ["whoami" => $this]);
      $this->_testCase = $testCase;
  }

  public function addError(string $message, mixed $code): self {
    $this->_err = new TestMessageError($message, $code);
    return $this;
  }

  public function getLastError(): ?string {
    return $this->_err ?? null;
  }

  public function andWhen(string $description, Callable $callback): self
  {
    return $this->when($description, $callback, self::PREFIX_AND_WHEN);
  }
  /**
   * @param string $description
   * @param Callable $callback
   */
  public function when(string $description, Callable $callback, string $prefix = self::PREFIX_WHEN): self
  {
    $this->_testCase->subiteration($prefix.' '.$description);
    $func = new \ReflectionFunction($callback);
    $args = [];
    foreach($func->getParameters() as $param)
    {
      $args[$param->getName()]=in_array($param->getName(), array_keys($this->_args)) ? $this->_args[$param->getName()] : null;
    }

    $callback(...$args);

    foreach($args as $argName => $argValue)
      $this->_args[$argName]=$argValue;
    return $this;
  }

  public function andThen(string $description, Callable $callback, mixed $result, ?Callable $onFail=null): self
  {
    return $this->then(description: $description, callback: $callback, result: $result, prefix: self::PREFIX_AND_THEN, onFail: $onFail);
  }

  public function then(string $description, Callable $callback, mixed $result, string $prefix=self::PREFIX_THEN, ?Callable $onFail=null): self
  {
    $this->_testCase->subiteration($prefix.' '.$description);

    $func = new \ReflectionFunction($callback);
    $args = [];
    foreach($func->getParameters() as $param)
    {
      $args[$param->getName()]=in_array($param->getName(), array_keys($this->_args)) ? $this->_args[$param->getName()] : null;
    }
    /** @var mixed $target */
    $target = $callback(...$args);

    if($target != $result && !empty($onFail)) {
      $func = new \ReflectionFunction($onFail);
      $args = [];
      foreach($func->getParameters() as $param) {
        $args[$param->getName()]=in_array($param->getName(), array_keys($this->_args)) ? $this->_args[$param->getName()] : null;
      }
      $onFail(...$args);
    }

    $onFailMessage = (null !== $this->_err) ? (string)$this->_err : "KO";
    $this->_testCase->compareTo($target, $result, 'OK', $onFailMessage);
    $this->_err = null;

    return $this;
  }
}
