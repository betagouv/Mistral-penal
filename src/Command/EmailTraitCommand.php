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
namespace App\Command;

use App\Utils\Env;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

trait EmailTraitCommand {
  private ?MailerInterface $_mailer=null;

  public function getMailer(): ?MailerInterface {
    return $this->_mailer;
  }

  public function setMailer(MailerInterface $mailer): self {
    $this->_mailer=$mailer;

    return $this;
  }

  public function send(string $to, string $subject, string $html): self {
    # envoi de l'email à l'administrateur fonctionnel
    $email = new Email();
    $email->from(Env::get('MAILER_FROM'));
    $email->to($to);
    $email->subject($subject);
    $email->html($html);
    $this->getMailer()->send($email);

    return $this;
  }
}
