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
namespace App\EventListener;

use App\Entity\CassiopeeImportJob;
use App\Entity\CassiopeeImportJobStatus;
use App\Message\ImportAvocatMessage;
use App\Service\Cassiopee\AbstractCassiopeeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event:LoginSuccessEvent::class, method: 'onLogin', priority: 1024)]
final class LoginListener {
    public function __construct(
      private EntityManagerInterface $em,
      private MessageBusInterface $bus,
      private RequestStack $requestStack
    ) {}

    public function onLogin(LoginSuccessEvent $event): void
    {
      $cookies = AbstractCassiopeeService::getCookies($this->requestStack->getSession());

      if ($cookies === null) {
        return ;
      }

      $importJob = new CassiopeeImportJob();
      $importJob->setStatus(CassiopeeImportJobStatus::NEW);
      $importJob->setProgress(0);
      $this->em->persist($importJob);
      $this->em->flush();


      $this->bus->dispatch(new ImportAvocatMessage(
          $event->getUser()->getId(),
          $importJob->getId(),
          $cookies
      ));
    }
}
