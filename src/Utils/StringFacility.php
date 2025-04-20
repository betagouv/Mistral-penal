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
namespace App\Utils;

class StringFacility
{
  /**
   * Fonction de formatage en chaîne de type snakeCase
   *
   * @param string $model
   * @return string
   */
  public static function toSnakeCase(string $model): string {
      $tmp = preg_replace("/([A-Z]+)/","_$1", $model);
      $tmp = preg_replace("/^[_]/","",mb_strtolower($tmp));
      return $tmp;
  }

  public static function toCamelCase(string $model): string {

    $tmp = explode("_", $model);
    foreach($tmp as $index => $_)
      if($index > 0)
        $tmp[$index] = ucfirst($_);
    return implode("", $tmp);
  }

  public static function toDate(string $model): ?\DateTime {
    if(
      preg_match("/^(?<year>\d{4})[\/-](?<month>\d{2})[\/-](?<day>\d{2})$/", trim($model), $matches)
      ||
      preg_match("/^(?<day>\d{2})\/(?<month>\d{2})\/(?<year>\d{4})$/", trim($model), $matches)
    )
      return new \DateTime($matches['year'].'-'.$matches['month'].'-'.$matches['day']);
    return null;
  }

  public static function toInt(string $model): ?int {
    return (preg_match("/^\d+$/", trim($model))) ? (int)$model : null;
  }

  public static function isText(string $model): bool {
    return (mb_strlen(trim($model))>255);
  }
}
