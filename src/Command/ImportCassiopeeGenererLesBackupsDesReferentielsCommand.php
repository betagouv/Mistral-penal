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

use App\Entity\CategoriePenale;
use App\Entity\DecisionPrevention;
use App\Entity\DecisionSanction;
use App\Entity\FormeJuridique;
use App\Entity\ModaliteParticipation;
use App\Entity\LienJuridique;
use App\Entity\LienSocial;
use App\Entity\ModeComparution;
use App\Entity\ModeConvocation;
use App\Entity\ModePoursuite;
use App\Entity\ModulationPeine;
use App\Entity\Nationalite;
use App\Entity\Structure;
use App\Entity\Pays;
use App\Entity\RenvoiMotif;
use App\Entity\SituationFamilliale;
use App\Utils\StringFacility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-cassiopee:generer-les-backups-des-referentiels',
    description: 'Génération du backup des référentiels (nécessaire pour la mise au point sous CODEO sur les premières phases du projet)',
)]
class ImportCassiopeeGenererLesBackupsDesReferentielsCommand extends Command 
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

    private static function protectSQL(?string $data): ?string
    {
        if(null === $data)
          return null;

        $tmp = str_replace(["'"],["''"],$data);
        return $tmp;
    }

    private function generateCSV(string $classname, string $folder, array $additionalColumns=[]): void
    {
      $conn = $this->em->getConnection();
      $entities = $this->em->getRepository($classname)->findAll();
      $tab = [];
      $garbage=[];
      foreach($entities as $entity) {
        $code = self::protectSQL($entity->getCode());
        $libelle = self::protectSQL($entity->getLibelle());
        $mnemo = self::protectSQL($entity->getMnemo());
        if(in_array($libelle, $garbage))
          continue;
        $garbage[]=$libelle;
        $ac=[];
        $hac=[];
        foreach($additionalColumns as $additionalColumn) {
          $method=$additionalColumn['method'];
          $column=$additionalColumn['column'];

          $type = gettype($entity->$method());
          $tmp = ($type == 'object') ? $entity->$method()->getId() : $entity->$method();
          $ac[]="'".self::protectSQL($tmp)."'";
          $hac[]=$column;
        }
        $sac=(count($hac)?', ':'').implode(", ",$ac);
        $shac=(count($hac)?', ':'').implode(", ",$hac);
        $term = preg_quote('\\');
        $filename = StringFacility::toSnakeCase(preg_replace("/^(.*)".$term."(?<filename>[^".$term."]+)$/","$2",$classname));
        $idSeq = 'webapp.'.$filename.'_id_seq';
        if($code)
          $tab[] = "INSERT INTO webapp.$filename(id, code, libelle, mnemo$shac)VALUES(nextval('$idSeq'), '$code', '$libelle','$mnemo'$sac) ON CONFLICT DO NOTHING;";
      }
      $content = implode("\r\n", $tab);
      file_put_contents($folder.'/'.$filename.'.sql', $content);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $refFolder = realpath(__DIR__.'/../../docs/referentiel');

        $io->title("Lancement de la génération des CSV de sauvegarde");

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
          Structure::class
        ];

        foreach($referentiels as $referentiel) {
          $additionalColumns=[];
          if(in_array($referentiel, [
            Structure::class
          ])) {
            $additionalColumns[]=[
              'column' => 'origine',
              'method' => 'getOrigine',
            ];
            $additionalColumns[]=[
              'column' => 'cour_appel_id',
              'method' => 'getCourAppel',
            ];
          }
          if(in_array($referentiel, [
            DecisionPrevention::class,
            DecisionSanction::class,
            ModulationPeine::class,
            RenvoiMotif::class
          ])) {
            $additionalColumns[]=[
              'column' => 'ordre',
              'method' => 'getOrdre',
            ];
          }
          $this->generateCSV($referentiel, $refFolder, $additionalColumns);
        }

        $io->success('Génération des backups réalisée');

        return Command::SUCCESS;
    }
}
