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

use App\Entity\Security\Account;
use App\Repository\Security\AccountRepository;
use App\Utils\Validator\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin:liste'
)]
class UserListCommand extends Command
{
    /** @var EntityManagerInterface */
    private $_em;
    /** @var AccountRepository */
    private $_ar;
    /** @var Validator */
    private $_validator;

    public function __construct(
      EntityManagerInterface $em,
      AccountRepository $ar,
      Validator $validator
    ) {
      parent::__construct();
      $this->_em = $em;
      $this->_ar = $ar;
      $this->_validator = $validator;
    }

    public function getEntityManager(): EntityManagerInterface
    {
      return $this->_em;
    }

    public function getAccountRepository(): AccountRepository
    {
      return $this->_ar;
    }

    public function getValidator(): Validator
    {
      return $this->_validator;
    }

    protected function configure(): void
    {
        $this
          ->setDescription("Liste des utilisateurs déclarés")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        /** @var SymfonyStyle $io */
        $io = new SymfonyStyle($input, $output);
        /** @var AccountRepository $ar */
        $ar = $this->getAccountRepository();
        /** @var Collection<int, Account> */
        $allUsers = $ar->findAdminFoncs();
        // Doctrine query returns an array of objects and we need an array of plain arrays
        $usersAsPlainArrays = array_map(function (Account $user) {
            return [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
            ];
        }, $allUsers);

        $io->table(
            ['ID', "Nom d'utilisateur", 'Email'],
            $usersAsPlainArrays
        );

        return Command::SUCCESS;
    }
}
