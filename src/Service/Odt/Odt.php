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

class Odt {

  const TYPE_PLAINTEXT = 'text';
  const TYPE_HTML_WITH_RN = 'html_with_rn';
  const TYPE_HTML = 'html';

  use ParserTrait;

  private ?string $_originFilename=null;
  private ?string $_temporaryFolder = null;
  private ?\SimpleXMLElement $_content = null;
  private ?\SimpleXMLElement $_styles = null;
  private ?string $_xml = null;
  private ?string $_xml_styles = null;
  public function getSection(string $name): ?Section
  {
    $section = new Section($name, $this->getContent()->asXml());
    return $section;
  }

  public function appendSection(Section $section, ?string $tagContainer=null): self
  {
    $name = $section->getName();
    $str = "{% render ".$section->getName()." %}";
    $posRenderBefore = strpos($this->getXML(), $str);
    $len = strlen($str);
    ################
    if(null !== $tagContainer) {
      $posRenderBefore = $this->findNearestTagFromPosition(
        xml: $this->getXML(),
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
    $xmlSplitBefore = substr($this->getXML(), 0, $posRenderAfter);
    $xmlSplitAfter = substr($this->getXML(), $posRenderAfter);
    $newXml = $xmlSplitBefore.$section->getInstance().$xmlSplitAfter;
    #############
    $this->setXML($newXml);
    $this->flush();
    $section->reset();
    return $this;
  }

  public function getXML(): string
  {
    return $this->_xml;
  }

  public function setXML(string $xml): self
  {
    $this->_xml = $xml;
    return $this;
  }
  /**
   * @author yanroussel
   *
   * Fonction de mise à jour de l'objet XML après manipulation
   */
  public function flush(): self
  {
    $newXml = simplexml_load_string($this->getXML());
    $this->setContent($newXml);
    return $this;
  }

  public function setVars(array $data, string $type=self::TYPE_PLAINTEXT): self {
    $this->_xml = self::insertVars($this->_xml, $data, $type);
    $this->_xml_styles = self::insertVars($this->_xml_styles, $data, $type);
    $this->flush();
    return $this;
  }

  public function __construct(string $originFilename)
  {
    $this->_originFilename = $originFilename;
    $this->generateTemporaryFolder();
    if(false === $this->openDocument())
      throw new \Exception('Impossible d\'ouvrir le fichier "'.$originFilename.'"');
    $content = file_get_contents($this->getTemporaryFolder().'/content.xml');
    $styles = file_get_contents($this->getTemporaryFolder().'/styles.xml');
    $this->_content = $content ? simplexml_load_string($content) : null;
    $this->_styles = $styles ? simplexml_load_string($styles) : null;
    $this->_xml = self::cleanupODT($this->_content->asXml());
    $this->_xml_styles = self::cleanupODT($this->_styles->asXml());
    $this->flush();
  }

  public function getContent(): ?\SimpleXMLElement
  {
    return $this->_content;
  }

  public function setContent(\SimpleXMLElement $element): self
  {
    unset($this->_content);
    $this->_content = $element;
    return $this;
  }

  public function getOriginFilename(): ?string
  {
    return $this->_originFilename;
  }

  public function generateTemporaryFolder(): string
  {
    $folder = sys_get_temp_dir().'/'.uniqid();
    mkdir($folder);
    $this->_temporaryFolder = $folder;
    return $folder;
  }

  public function getTemporaryFolder(): string
  {
    return $this->_temporaryFolder;
  }

  public function save(string $filename): bool
  {
    // mise à jour du content.xml
    $doc = new \DOMDocument();
    $doc->formatOutput = TRUE;
    $xml = $this->getXML();
    $xml = self::removeBlockTags($xml);
    $doc->loadXML($xml);

    $xml = $doc->saveXML();
    file_put_contents($this->getTemporaryFolder().'/content.xml', $xml);

    // mise à jour du styles.xml
    $doc = new \DOMDocument();
    $doc->formatOutput = TRUE;
    $xml = $this->_xml_styles;
    $xml = self::removeBlockTags($xml);
    $doc->loadXML($xml);

    $xml = $doc->saveXML();
    file_put_contents($this->getTemporaryFolder().'/styles.xml', $xml);
    return $this->saveDocument($filename);
  }

  private function saveDocument(string $filename): bool {
    $zip = new \ZipArchive();

    if (false === $zip->open($filename, \ZIPARCHIVE::CREATE))
        return false;

    $temporaryFolder = $this->getTemporaryFolder();
    if (is_dir($temporaryFolder) === true) {
      $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($temporaryFolder), \RecursiveIteratorIterator::SELF_FIRST);
      foreach($files as $file) {
        $outputFile = realpath((string)$file);
        if(true === is_dir($outputFile)) {
          $dirName = str_replace($temporaryFolder.DIRECTORY_SEPARATOR, '', $outputFile.DIRECTORY_SEPARATOR);
          $zip->addEmptyDir($dirName);
        }
        elseif(true === is_file($outputFile)) {
          $fileName = str_replace($temporaryFolder.DIRECTORY_SEPARATOR, '', $outputFile);
          $zip->addFromString($fileName, file_get_contents($outputFile));
        }
      }
    }
    return $zip->close();
  }

  private function openDocument(): bool {
    /** @var string $zipInputFile */
    $zipInputFile = $this->getOriginFilename();
    /** @var ZipArchive $zip */
    $zip = new \ZipArchive();
    /** @var string $outputFolder */
    $outputFolder = $this->getTemporaryFolder();
    $res = $zip->open($zipInputFile);
    if ($res === true) {
        $zip->extractTo($outputFolder);
        $zip->close();
        return true;
    }
    return false;
  }
}
