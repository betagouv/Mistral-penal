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

/**
 * Classe utilisée pour la récupération des variables d'environnement
 *
 */
class Env
{
  /**
   * La donnée est t'elle une tableau
   *
   * @param string $param Valeur à analyser
   * @return bool
   */
  private static function isArray(string $param): bool {
    return preg_match("/^[ ]*[{](.*)[}][ ]*$/", $param);
  }

  /**
   * Conversion de la donnée textuelle en donnée typée
   *
   * @param string $param Paramètre à convertir
   * @return mixed Paramètre typé
   */
  private static function cast(string $param): mixed {
    $val = trim($param);
    if(in_array($val,['true','false']))
      $val = ($val === 'true');
    elseif(preg_match("/^\d$/",$val))
      $val = (int)$val;
    else
      $val = preg_replace("/(^'|'$)/","", $val);
    return $val;
  }

  /**
   * Récupération d'un tableau de donnée d'un paramètre d'environnement
   *
   * @param string $param Paramètre à convertir en tableau
   * @return array Tableau résultant
   */
  private static function getArray(string $param): array {
    $output = [];
    if(true === self::isArray($param)) {
      preg_match_all("/[ ]*[']?(?<key>[^'{,]+)[']?[ ]*[:][ ]*(?<value>[']?[^},]+[']?)[ ]*/i", $param, $matches);
      $count = count($matches['key']);
      for($i=0;$i<$count;$i++) {
        $val = self::cast($matches['value'][$i]);
        $output[$matches['key'][$i]]=$val;
      }
    }
    return $output;
  }

  final public static function set(string $param, mixed $value): void {
    $_ENV[$param] = $value;
  }
  /**
   * Récupération de la variable d'environnement courante
   *
   * @param string $param
   * @return mixed
   */
  final public static function get(string $param): mixed {
    $block = $_ENV[$param] ?? null;
    if(null === $block)
      return null;
    return self::isArray($block) ? self::getArray($block) : self::cast($block);
  }
}
