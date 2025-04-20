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
namespace App\MessageHandler;

use App\Repository\AvocatRepository;
use App\Service\Cassiopee\AvocatService;
use App\Message\ImportAvocatMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ImportAvocatMessageHandler {
  public function __construct(
    private EntityManagerInterface $em,
    private AvocatService $avocatService,
    private AvocatRepository $avocatRepository
  ) {

  }

  public function __invoke(ImportAvocatMessage $message)
  {
    $accountId = $message->getAccountId();
    $cookies = $message->getCookies();
    $importJobId = $message->getImportJobId();

    $this->avocatService->setCookies($cookies);

    $avocats = $this->avocatService->getAvocats();
    $this->avocatRepository->insertOrUpdate($avocats);

  }
}
