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

trait ParserTrait {

  public static function getElementsByTypeTag(string $xml, string $typeTag): array
  {
    $matchBegin = '\{%[ ]*'.$typeTag.'[ ]+([^%]+)[ ]*%\}';
    preg_match_all("/$matchBegin/", $xml, $matches);
    $tab=[];
    foreach($matches[0] as $index => $match)
      $tab[]=[
        'html' => $match,
        'tag' => trim($matches[1][$index])
      ];
    return $tab;
  }
  public static function removeBlockTags(
    string $xml
  ): string
  {
    $subjects = [
      ['block', 'endblock'],
      ['if', 'endif'],
    ];
    foreach($subjects as $subject) {
      $blocks = self::getElementsByTypeTag($xml, $subject[0]);
      $endblocks = self::getElementsByTypeTag($xml, $subject[1]);
      foreach($blocks as $block) {
        $firstPos = strpos($xml, $block['html']);
        $lastPos = false;
        foreach($endblocks as $endblock)
          if($endblock['tag']==$block['tag'])
            $lastPos = strpos($xml, $endblock['html'])+strlen($endblock['html']);
        $xml = substr($xml,0,$firstPos).substr($xml, $lastPos);
      }
    }
    $xml        = preg_replace("/{%[^%]+%}/i","", $xml);
    $xml        = preg_replace("/{\#[^#]+\#}/i","", $xml);
    $xml        = str_replace(["\t","\r","\n"],"", $xml);
    return $xml;
  }

  public static function normalizeHtml(?string $html): ?string
  {
    $forbiddenTags = [
      'a',
      'abbr',
      'address',
      'audio',
      'blockquote',
      'cite',
      'del',
      'img',
      'figure',
      'link',
      'mark',
      'meta',
      'q',
      'script',
      'span',
      'style',
      'sub',
      'sup',
      'title',
      'video',
    ];
    if(null === $html)
      return null;
    // suppression des attributs polluants du HTML
    $html = preg_replace("/<(?<tag>[^>]+)[ ]class[=][\"]([^>]+)[\"]([ ]|>)/","<$1$3",$html);
    $html = preg_replace("/<(?<tag>[^>]+)[ ]style[=][\"]([^>]+)[\"]([ ]|>)/","<$1$3",$html);
    $html = preg_replace("/<\/?(".implode("|",$forbiddenTags).")>/","",$html);
    return $html;
  }
  public static function convertHtmlToODT(?string $html): string
  {
    $html = self::normalizeHtml($html);
    $odt = $html??'';

    $odt = str_replace([
      "<strong>",
      "</strong>",
      "<u>",
      "</u>",
      '<em>',
      "</em>",
      '<p>',
      '</p>',
      "\r",
      "\n",
      "\t",
    ], [
      '<text:span text:style-name="Strong_20_Emphasis">',
      '</text:span>',
      '<text:span text:style-name="T35">',
      '</text:span>',
      '<text:span text:style-name="T17">',
      '</text:span>',
      '<text:p text:style-name="P44">',
      '</text:p>',
      '',
      '',
      '',
    ], $odt);
    $odt = html_entity_decode($odt);
    $odt = str_replace(["&"],['&amp;'],$odt);
    return $odt;
  }
  public static function cleanupODT(
    string $xml
  ): string
  {
    /**
     * @author yanroussel
     * @description rapprochement des accolades pour donner du sens
     */
    $a = preg_quote('{');
    $b = preg_quote('}');
    $newXml = $xml;
    /**
     * @author yanroussel
     * @description Déplacement des balises parasites à l'intérieur d'un bloc
     * @example {</text:span><text:span text:style-name="T12">{</text:span><text:span text:style-name="T11">president</text:span><text:span text:style-name="T12">} => <text:span text:style-name="T11"></text:span>{</text:span><text:span text:style-name="T12">{</text:span>president<text:span text:style-name="T12">}
     */
    do {
      $xml = $newXml;
      // système composé <></> : exclusion du système de balisage
      $newXml = preg_replace("/({[^}]*)([<][^\/][^>]+[>])([^<}]*)([<][\/][^>]+[>])/","$2$4$1$3", $xml);
    }
    while($newXml != $xml);
    /**
     * @author yanroussel
     * @description Déplacement des balises parasites à l'intérieur d'un bloc
     * @example {<text:span>% block toto %<\text:span>} => <text:span>{% block toto %}<\text:span>
     */
    do {
      $xml = $newXml;
      $newXml = preg_replace("/($a)([<][^>]+[>])/","$2$1",$xml);
      $newXml = preg_replace("/([<][^>]+[>])($b)/","$2$1",$newXml);
    }
    while($newXml != $xml);
    /**
     * @author yanroussel
     * @description Déplacement des balises parasites à l'intérieur d'un bloc
     * @example {% <\text:span>block toto %} => <\text:span>{% block toto %}
     */
    do {
      $xml = $newXml;
      $newXml = preg_replace("/($a)([^}]*)([<][^>]+[>])([^}]*)($b)/","$3$1$2$4$5",$xml);
    }
    /**
     * @author yanroussel
     * @description Déplacement des balises prises dans un bloc
     * @example {% block toto %}<\text:span>    => <\text:span>{% block toto %}
     *          {% endblock toto %}<\text:span> => <\text:span>{% endblock toto %}
     */
    while($newXml != $xml);
    do {
      $xml = $newXml;
      $newXml = preg_replace("/([{][^{]+[}])([<][\/][^>]+[>])/","$2$1",$xml);
    }
    while($newXml != $xml);
    /**
     * @author yanroussel
     * @description Elimination des espaces dans les variables
     * @example
     * {{          test             }} => {{test}}
     */
     while($newXml != $xml);
     do {
       $xml = $newXml;
       $newXml = preg_replace("/([{][{][^}]*)[ ]+([^}]*[}][}])/","$1$2",$xml);
     }
     while($newXml != $xml);
     /**
      * @author yanroussel
      * @description Déplacement des balises parasites à l'intérieur d'un bloc (nécessaire car doublabe des '{')
      * @example {<text:span>% block toto %<\text:span>} => <text:span>{% block toto %}<\text:span>
      */
     do {
       $xml = $newXml;
       $newXml = preg_replace("/($a)([<][^>]+[>])/","$2$1",$xml);
       $newXml = preg_replace("/([<][^>]+[>])($b)/","$2$1",$newXml);
     }
     while($newXml != $xml);
     /**
      * @author yanroussel
      * @description Incorporation des variables dans les bons encadrement XML
      * @example de </text:p>{{personne.parent.pere}} => de {{personne.parent.pere}}</text:p>
      */
     do {
       $xml = $newXml;
       $newXml = preg_replace("/([<]\/[^>]+[>])([{][{][^}]+[}][}])/","$2$1",$xml);
     }
     while($newXml != $xml);

     return $xml;
  }

  /**
   * Fonction d'identification d'un encadrement à partir d'une position
   *
   * @param string $xml XML de référence
   * @param string $tag TAG parent à gauche de la position courante
   * @param int $position position de référence
   * @param ?int $complementaryPosition Position de fermeture du parent
   * @return ?int
   */
  public static function findNearestTagFromPosition(
    string $xml,
    string $tag,
    int $position,
    ?int &$complementaryPosition=null,
    bool $isSection=false
  ): ?int
  {
    $xmlSplitBefore = substr($xml, 0, $position);
    $revXml = strrev($xmlSplitBefore);
    $revTag = strrev("<".$tag);
    $revPos = strpos($revXml, $revTag);
    if(true === $isSection) {
      $newRevPos = $revPos;
      do {
        $revPos = $newRevPos;
        $newRevPos = strpos($revXml, $revTag,$revPos+1);
      }while(false !== $newRevPos);
    }
    $posRenderBefore = ($revPos) ? strlen($revXml)-$revPos-strlen("<".$tag) : false;

    if(false === $posRenderBefore)
      return null;

    $xmlSplitAfter = substr($xml, $posRenderBefore);
    $complementaryTag = '</'.$tag.'>';
    $pos = strpos($xmlSplitAfter, $complementaryTag);
    if(true === $isSection) {
      $newPos = $pos;
      do {
        $pos = $newPos;
        $newPos = strpos($xmlSplitAfter, $complementaryTag, $pos+1);
      }while(false !== $newPos);
    }

    if($pos)
      $complementaryPosition = $pos+$posRenderBefore+strlen($complementaryTag);

    return (false !== $posRenderBefore) ? $posRenderBefore : null;
  }

  public static function insertVars(?string $xml,array $data, string $type=Odt::TYPE_PLAINTEXT): string {
    if(null === $xml)
      return "";
    foreach($data as $key => $value) {
      if(in_array($type, [Odt::TYPE_HTML, Odt::TYPE_HTML_WITH_RN])) {
        $newXml = $xml;
        do {
          $xml = $newXml;
          $newXml = preg_replace("/([<]text[:][^>]+[>])({{".$key."}})/i", "$2$1", $xml);
        }while($xml != $newXml);
      }

      if(Odt::TYPE_HTML_WITH_RN == $type) {
        $value = '<text:p text:style-name="P65">'.implode('</text:p><text:p text:style-name="P65">',explode("\r\n", $value)).'</text:p>';
      }
      $xml= str_replace("{{".$key."}}",$value, $xml);
    }
    return $xml;
  }
}
