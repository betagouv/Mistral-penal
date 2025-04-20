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

use App\Entity\Affaire;
use App\Entity\Audience;
use App\Entity\Personne;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\ar_EG\Person;
use App\Utils\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clear:outdated',
    description: "Fonction dédiée à la purge régulière des données réelles",
)]
class AudienceClearOutdatedCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $delai = Env::get('AUDIENCE_OUTDATED_DELAI');
        $audiences = $this->entityManager->getRepository(Audience::class)->findOutdated($delai);

        foreach($audiences as $audience) {
            $affaires = $audience->getAffaires();
            foreach($affaires as $affaire){
                $affaire->setAudience(null);
                $this->entityManager->remove($affaire);
            }
            $this->entityManager->remove($audience);
        }

        // clean personnes sans plus de relation
        $orphans = $this->entityManager->getRepository(Personne::class)->getOrphans();
        foreach($orphans as $orphan) {
            $this->entityManager->remove($orphan);
        }

        $this->entityManager->flush();

        $output->writeln('La purge de la base de donnée a été effectuée avec succès');
        return Command::SUCCESS;
    }

}
