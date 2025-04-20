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

namespace App\Command;

use App\Entity\CategoriePenale;
use App\Entity\DecisionPrevention;
use App\Entity\DecisionSanction;
use App\Entity\FormeJuridique;
use App\Entity\LienJuridique;
use App\Entity\LienSocial;
use App\Entity\ModaliteParticipation;
use App\Entity\ModeComparution;
use App\Entity\ModeConvocation;
use App\Entity\ModePoursuite;
use App\Entity\RenvoiMotif;
use App\Entity\ModulationPeine;
use App\Entity\Nationalite;
use App\Entity\Pays;
use App\Entity\SituationFamilliale;
use App\Entity\StatutPersonne;
use App\Utils\StringFacility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-cassiopee:charger-les-referentiels-depuis-les-backups',
    description: 'Chargement des référentiels à partir des fichiers SQL backup',
)]
class ImportCassiopeeChargerLesReferentielsDepuisLesBackupsCommand extends Command 
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

        $io->title("Lancement de la lecture des référentiels en sauvegarde");
        $refFolder = realpath(__DIR__.'/../../docs/referentiel');
        $referentiels = [
          CategoriePenale::class,
          DecisionPrevention::class,
          DecisionSanction::class,
          FormeJuridique::class,
          LienJuridique::class,
          LienSocial::class,
          ModaliteParticipation::class,
          ModeComparution::class,
          ModeConvocation::class,
          ModePoursuite::class,
          ModulationPeine::class,
          Nationalite::class,
          Pays::class,
          RenvoiMotif::class,
          SituationFamilliale::class,
          StatutPersonne::class,
        ];

        foreach($referentiels as $referentiel) {

          $io->note('Import du référentiel "'.$referentiel.'"');
          $this->importSQL($referentiel, $refFolder);
        }

        $io->success('Import des référentiels réalisés avec succès');

        return Command::SUCCESS;
    }

    public function importSQL(string $classname, $folder): void
    {
        $term = preg_quote('\\');
        $filename = StringFacility::toSnakeCase(preg_replace("/^(.*)".$term."(?<filename>[^".$term."]+)$/","$2",$classname));
        $handle = fopen("$folder/$filename.sql","r");
        if($handle) {
          $conn = $this->em->getConnection();
          $conn->getConfiguration()->setSQLLogger(null);
          while (($sql = fgets($handle)) !== false) {
            $conn
              ->prepare($sql)
              ->execute()
            ;
          }
        }
    }
}
