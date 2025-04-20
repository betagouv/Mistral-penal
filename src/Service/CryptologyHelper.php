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

use App\Contracts\EncryptionInterface;
use App\Utils\Env;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

class CryptologyHelper implements EncryptionInterface
{
  const TYPE_BOOL = 'b';
  const TYPE_STRING = 's';
  const TYPE_INTEGER = 'i';
  const TYPE_DATETIME = 'd';
  const TYPE_ARRAY = 'a';
  const SEPARATOR = '||';

  public static function explode(string $ciphertext): array {
    $tmp = explode(self::SEPARATOR,$ciphertext);
    return [
      'type' => $tmp[0],
      'ciphertext' => $tmp[1],
    ];
  }

  public static function implode(string $type, string $ciphertext): string {
    return $type.self::SEPARATOR.$ciphertext;
  }

  public static function get_raw_key(): ?string {
    return Env::get('DATABASE_ENCRYPTION_KEY');
  }

  public static function get_key(): ?EncryptionKey
  {
    $rawKey = self::get_raw_key();

    if ($rawKey == null) {
        return null;
    } 

    return KeyFactory::importEncryptionKey(new HiddenString($rawKey));
  }

  /**
   * Génération de la clé de chiffrement si inexistante (ignoré si déjà existante)
   *
   * @return bool
   */
  public static function generate_key(): string 
  {
    $enc_key = KeyFactory::generateEncryptionKey();
    return KeyFactory::export($enc_key)->getString();
  }

  public static function encrypt_date(?\DateTime $date): ?string
  {
    if(null === $date)
      return null;
    return self::encrypt_string(serialize($date));
  }

  public static function encrypt_int(?int $int): ?string
  {
    if(null === $int)
      return null;
    return self::encrypt_string(serialize($int));
  }

  public static function encrypt_bool(?bool $bool): ?string
  {
    if(null === $bool)
      return null;
    $int = (true === $bool) ? 1 : 0;
    return self::encrypt_string(serialize($int));
  }

  public static function encrypt_array(?array $tab): ?string
  {
    if(null === $tab)
      return null;
    return self::encrypt_string(serialize($tab));
  }

  public static function decrypt_array(?string $ciphertext): ?array
  {
    /** @var ?int $decryptInt */
    $decrypt = self::decrypt_string($ciphertext);
    if(null === $decrypt)
      return null;
    return unserialize($decrypt);
  }

  public static function decrypt_date(?string $ciphertext): ?\DateTime
  {
    if(null === $ciphertext)
      return null;
    $tmp = self::decrypt_string($ciphertext);
    return unserialize($tmp);
  }

  public static function decrypt_bool(?string $ciphertext): ?bool
  {
    /** @var ?int $decryptInt */
    $decryptInt = self::decrypt_int($ciphertext);
    if(null === $decryptInt)
      return null;
    return (1==$decryptInt);
  }

  public static function decrypt_int(?string $ciphertext): ?int
  {
    if(null === $ciphertext)
      return null;
    $tmp = self::decrypt_string($ciphertext);
    return unserialize($tmp);
  }

  public static function encrypt(mixed $text): ?string
  {
    if(null === $text)
      return null;
    if((true===$text)||(false===$text))
      return self::implode(self::TYPE_BOOL,self::encrypt_bool($text));
    if(is_int($text))
      return self::implode(self::TYPE_INTEGER,self::encrypt_int($text));
    if($text instanceof \DateTime)
      return self::implode(self::TYPE_DATETIME,self::encrypt_date($text));
    if(is_array($text))
      return self::implode(self::TYPE_ARRAY,self::encrypt_array($text));
    return self::implode(self::TYPE_STRING,self::encrypt_string($text));
  }

  public static function encrypt_string(?string $text): ?string
  {
    if(null === $text)
      return null;
    $encryptionKey = self::get_key();
    $message = new HiddenString($text);
    return Symmetric::encrypt($message, $encryptionKey);
  }

  public static function decrypt(?string $ciphertext): mixed
  {
    if(null === $ciphertext)
      return null;

    $tmp = self::explode($ciphertext);
    switch($tmp['type']) {
      case self::TYPE_DATETIME:
        return self::decrypt_date($tmp['ciphertext']);
      case self::TYPE_BOOL:
        return self::decrypt_bool($tmp['ciphertext']);
      case self::TYPE_INTEGER:
        return self::decrypt_int($tmp['ciphertext']);
      case self::TYPE_ARRAY:
        return self::decrypt_array($tmp['ciphertext']);
      case self::TYPE_STRING:
      default:
        return self::decrypt_string($tmp['ciphertext']);
    }
  }

  public static function decrypt_string(?string $ciphertext): ?string
  {
    if(null === $ciphertext)
      return null;
    $encryptionKey = self::get_key();
    $decrypted = Symmetric::decrypt($ciphertext, $encryptionKey);
    return $decrypted->getString();
  }

  public static function pepperedHash(string $text): string {
    $pepper = self::get_raw_key();

    if ($pepper == null) {
        throw new \Exception("Cannot find pepper for pepperedHash generation !");
    }

    return hash('sha256', $pepper . $text);
  }
}
