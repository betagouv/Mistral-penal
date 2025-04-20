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

use App\Entity\Structure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-divers:charger-les-referentiels-depuis-les-csvs',
    description: 'Chargement des TJ & CA à partir de fichiers CSV',
)]
class ImportChargerLesStructuresDepuisLesCSVsCommand extends Command 
{
    public function __construct(
      private EntityManagerInterface $em
    ) {
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
        $io = new SymfonyStyle($input, $output);

        $io->title("Lancement de la lecture des csvs");
        $refFolder = realpath(__DIR__.'/../../docs/csv');
        $referentiels = [
          Structure::class => '2024-competences-territoriales',
        ];

        foreach($referentiels as $referentiel => $file) {

          $io->note('Import du référentiel "'.$referentiel.'"');

          $this->importCSV($referentiel, "$refFolder/$file");
        }

        $io->success('Import des référentiels réalisés avec succès');

        return Command::SUCCESS;
    }

    public function importCSV(string $classname, string $filename): void
    {
        $sr = $this->em->getRepository($classname);
        $handle = fopen("$filename.csv","r");
        $header = false;
        if($handle) {
          $conn = $this->em->getConnection();
          $conn->getConfiguration()->setSQLLogger(null);
          while (($csvLine = fgets($handle)) !== false) {
            if(false === $header)
            {
              $header = true;
              continue;
            }
            list($_,$_, $origineCA, $codeCA, $libelleCA,
              $origineTJ, $codeTJ, $libelleTJ)  = explode(';',$csvLine);
            /** @var ?Structure $courAppel */
            $courAppel = $sr->findOneBy(['code' => $codeCA]);
            if(null === $courAppel) {
              $courAppel = new Structure();
              $courAppel->setCode($codeCA);
              $courAppel->setLibelle(utf8_encode($libelleCA));
              $courAppel->setMnemo($codeCA);
              $courAppel->setOrigine($origineCA);
              $sr->save($courAppel,true);
            }

            /** @var ?Structure $tj */
            $tj = $sr->findOneBy(['code' => $codeTJ]);
            if(null === $tj) {
              $tj = new Structure();
              $tj->setCode($codeTJ);
              $tj->setLibelle(utf8_encode($libelleTJ));
              $tj->setMnemo($codeTJ);
              $tj->setOrigine($origineTJ);
              $tj->setCourAppel($courAppel);
              $sr->save($tj,true);
            }
          }
        }
    }
}
