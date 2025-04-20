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

use App\Service\PasswordGenerator;
use App\Entity\Security\Account;
use App\Repository\Security\AccountRepository;
use App\Utils\Validator\Validator;
use Doctrine\ORM\EntityManagerInterface;
use App\Utils\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:admin:ajout',
    description: 'ajouter un nouvel admin fonctionnel',
)]
class UserAddNewCommand extends Command
{
    use EmailTraitCommand;

    public function __construct(
        private EntityManagerInterface $em,
        private AccountRepository $accountRepository,
        private Validator $validator,
        private UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ) {
        parent::__construct();
        $this->setMailer($mailer);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Création d\'un nouveau compte')
            ->addArgument('username', InputArgument::REQUIRED, 'Nom d\'utilisateur')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = mb_strtolower($input->getArgument('username'));

        $io->text(' > <info>Nom d\'utilisateur</info>: '.$username);
        
        $account = $this->accountRepository->findOneByUsername($username);

        $password = null;

        if(null !== $account) {
          $io->error("Echec à l'ajout. Le compte $username existe déjà");
          return Command::FAILURE;
        }
        
        $password = "MistralGagnant2024!";

        $check = false;
        do {
          /** @var ?string $email */
          $email = mb_strtolower($io->ask('Email', null, [$this->validator, 'validateEmail']));
          $account = $this->accountRepository->findOneByEmail($email);

          if(null !== $account)
            $io->error("L'email $email est déjà déclaré. Veuillez en saisir un autre");
          else
            $check = true;
        }while(false === $check);

        /**
         * @author yroussel
         *
         * Création d'un nouveau compte
         */
        $account = new Account();
        $account->setUsername($username);
        $account->setEmail($email);
        $account->setPassword("FAKE_PASSWORD");
        $account->setDateChangementMDP(null);
        $account->setMnemo(AccountRepository::generate_random_mnemo());
          
        $io->text(' > <info>Rôle</info>: '.Account::ROLE_ADMIN_FONC);
        $account->addRole(Account::ROLE_ADMIN_FONC);
        
        $account->setPassword(
            $this->passwordHasher->hashPassword($account, $password)
        );
        $this->accountRepository->add($account, true);

        $io->success("Le compte $username a été ajouté avec succès !");

        /*
        $html = str_replace([
          "{{username}}",
          "{{password}}"
        ],[
          $account->getUsername(),
          $password
        ],"
        <div>
        <p>Bonjour,<br>
        MISTRAL vous a inscrit comme administrateur fonctionnel de l'application. Vous aurez la charge
        de l'administration des utilisateurs habilités à utiliser MISTRAL.</p>
        <p>Vos accès: <br>
          <table>
            <tr>
              <th>Nom d'utilisateur</th><td>{{username}}</td>
            </tr>
            <tr>
              <th>Mot de passe</th><td>{{password}}</td>
            </tr>
          </table>
        </p>
        </div>
        ");

        # envoi de l'email à l'administrateur fonctionnel
        $this->send(
          to: $account->getEmail(),
          subject: 'Création de votre compte administrateur fonctionnel pour MISTRAL',
          html: $html
        );*/

        return Command::SUCCESS;
    }
}
