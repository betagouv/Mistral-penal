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
namespace App\Controller\Avocat;

use App\Entity\Security\Account;
use App\Repository\Security\ServiceRepository;
use App\Repository\AvocatRepository;
use App\Service\Cassiopee\AvocatService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AsController]
class GetImport extends AbstractController {

  public function __construct(
    private ServiceRepository $sr,
    private AvocatRepository $avocatRepository,
    private TranslatorInterface $trans,
    private AvocatService $avocatService,
    private LoggerInterface $logger
  ) {
  }

  public function __invoke(SessionInterface $session) {
    /** @var Account $user */
    $user = $this->getUser();
    $services = $this->sr->findAllByUser($user);
    /** @var array $avocats */
    $this->avocatService->restoreCookies();
    $avocats = $this->avocatService->getAvocats();

    $this->avocatRepository->insertOrUpdate($avocats);

    return $avocats;
  }
}
