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

use App\Repository\Security\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:refresh-username-hashes',
    description: 'recompute username hashes',
)]
class RefreshUsernameHashesCommand extends Command
{

    public function __construct(private EntityManagerInterface $em) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $batchSize = 20;
        $i = 0;

        $q = $this->em->createQuery('SELECT a FROM App\Entity\Security\Account a');

        foreach ($q->toIterable() as $user) {
            $io->info("updating user " . $user->getUsername() . '...');
            $user->setUsername($user->getUsername());
            $i++;

            if (($i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();

        $i = 0;

        $q = $this->em->createQuery('SELECT a FROM App\Entity\Security\UtilisateurAccredite a');

        foreach ($q->toIterable() as $user) {
            $io->info("updating user " . $user->getUsername() . '...');
            $user->setUsername($user->getUsername());
            $i++;

            if (($i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }
        
        $this->em->flush();

        $io->success('Successfully updated all username hashes !');

        return Command::SUCCESS;
    }
}
