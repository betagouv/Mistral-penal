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
use App\Entity\FormeJuridique;
use App\Entity\LienJuridique;
use App\Entity\LienSocial;
use App\Entity\ModeComparution;
use App\Entity\ModeConvocation;
use App\Entity\ModePoursuite;
use App\Entity\Nationalite;
use App\Entity\Pays;
use App\Entity\SituationFamilliale;
use App\Service\RemoteWebService;
use Doctrine\ORM\EntityManagerInterface;
use App\Utils\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(
    name: 'app:import-cassiopee:recuperer-les-referentiels',
    description: 'Récupération des référentiels depuis Cassiopée via l\'exploitation du RPA',
)]
class ImportCassiopeeRecupererLesReferentielsCommand extends Command 
{
    public function __construct(
      private RemoteWebService $rws,
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

    private function generateLienJuridique(SymfonyStyle $io) {
      $items = [
        ['code' => '7', 'libelle' => 'pas de lien juridique'],
        ['code' => '1', 'libelle' => 'Représentant légal'],
        ['code' => '3', 'libelle' => 'Tiers'],
        ['code' => '2', 'libelle' => 'Intervenant'],
        ['code' => '6', 'libelle' => 'Administrateur Ad-hoc'],
        ['code' => '4', 'libelle' => 'Déclarant'],
        ['code' => '9', 'libelle' => 'Mandataire judiciaire'],
      ];

      $classname = LienJuridique::class;
      $io->text("Téléchargement du référentiel 'Lien juridique'");
      foreach($items as $item) {
        $entity = $this->getEntityFromArray($item, $classname);
        unset($entity);
      }
    }

    private function generateLienSocial(SymfonyStyle $io) {
      $items = [
        ['code' => '0', 'libelle' => ''],
        ['code' => '41', 'libelle' => 'a la garde de'],
        ['code' => '25', 'libelle' => 'ami'],
        ['code' => '26', 'libelle' => 'amie'],
        ['code' => '27', 'libelle' => 'beau frère'],
        ['code' => '37', 'libelle' => 'beau père'],
        ['code' => '9', 'libelle' => 'beau-fils'],
        ['code' => '10', 'libelle' => 'belle fille'],
        ['code' => '58', 'libelle' => 'belle mère'],
        ['code' => '28', 'libelle' => 'belle soeur'],
        ['code' => '52', 'libelle' => 'cousin'],
        ['code' => '53', 'libelle' => 'cousine'],
        ['code' => '7', 'libelle' => 'demi frère'],
        ['code' => '8', 'libelle' => 'demi soeur'],
        ['code' => '34', 'libelle' => 'divorcé de'],
        ['code' => '49', 'libelle' => 'famille d\'accueil'],
        ['code' => '4', 'libelle' => 'fille adoptive de'],
        ['code' => '2', 'libelle' => 'fille de'],
        ['code' => '23', 'libelle' => 'filleul'],
        ['code' => '24', 'libelle' => 'filleule'],
        ['code' => '3', 'libelle' => 'fils adoptif de'],
        ['code' => '1', 'libelle' => 'fils de'],
        ['code' => '5', 'libelle' => 'frère'],
        ['code' => '39', 'libelle' => 'gardiens de'],
        ['code' => '40', 'libelle' => 'gardiens de fait de'],
        ['code' => '38', 'libelle' => 'gardé de fait'],
        ['code' => '60', 'libelle' => 'grand mère maternelle'],
        ['code' => '61', 'libelle' => 'grand mère paternelle'],
        ['code' => '43', 'libelle' => 'grand père maternel de'],
        ['code' => '44', 'libelle' => 'grand père paternel de'],
        ['code' => '56', 'libelle' => 'marraine'],
        ['code' => '33', 'libelle' => 'mère'],
        ['code' => '51', 'libelle' => 'mère adoptive de'],
        ['code' => '21', 'libelle' => 'neveu'],
        ['code' => '22', 'libelle' => 'nièce'],
        ['code' => '36', 'libelle' => 'oncle'],
        ['code' => '35', 'libelle' => 'parrain'],
        ['code' => '59', 'libelle' => 'personne digne de confiance'],
        ['code' => '13', 'libelle' => 'petit fils (lien grand père maternel)'],
        ['code' => '11', 'libelle' => 'petit fils (lien grand père paternel)'],
        ['code' => '14', 'libelle' => 'petit fils (lien grand-mère maternelle)'],
        ['code' => '12', 'libelle' => 'petit fils (lien grand-mère paternelle)'],
        ['code' => '17', 'libelle' => 'petite fille (lien grand père maternel)'],
        ['code' => '15', 'libelle' => 'petite fille (lien grand père paternel)'],
        ['code' => '18', 'libelle' => 'petite fille (lien grand-mère maternelle)'],
        ['code' => '16', 'libelle' => 'petite fille (lien grand-mère paternelle)'],
        ['code' => '20', 'libelle' => 'placé sous la garde de'],
        ['code' => '32', 'libelle' => 'père'],
        ['code' => '50', 'libelle' => 'père adoptif de'],
        ['code' => '6', 'libelle' => 'soeur'],
        ['code' => '57', 'libelle' => 'tante'],
        ['code' => '54', 'libelle' => 'veuf de'],
        ['code' => '55', 'libelle' => 'veuve de'],
        ['code' => '31', 'libelle' => 'vit avec'],
        ['code' => '30', 'libelle' => 'épouse'],
        ['code' => '29', 'libelle' => 'époux'],
        ['code' => '45', 'libelle' => '÷( Parents )'],
        ['code' => '42', 'libelle' => '÷( grands parents )'],
        ['code' => '47', 'libelle' => '÷( sous l\'autorité de )'],
        ['code' => '48', 'libelle' => '÷( sous la responsabilité de )'],
        ['code' => '46', 'libelle' => '÷( sous la tutelle de )'],
      ];

      $classname = LienSocial::class;
      $io->text("Téléchargement du référentiel 'Lien social'");
      foreach($items as $item) {
        $entity = $this->getEntityFromArray($item, $classname);
        unset($entity);
      }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title("Démarrage de l'import des référentiels depuis Cassiopee via le RPA");

        $referentiels = [
          [
            'name' => 'paysimmatriculation',
            'label' => 'Pays',
            'class' => Pays::class,
          ],
          [
            'name' => 'poursuite',
            'label' => 'Mode de poursuite',
            'class' => ModePoursuite::class,
          ],
          [
            'name' => 'nationalite',
            'label' => 'Nationalité',
            'class' => Nationalite::class,
          ],
          [
            'name' => 'categoriePenale',
            'label' => 'Catégorie pénale',
            'class' => CategoriePenale::class,
          ],
        ];

        parent::execute($input, $output);

        $this->generateLienJuridique($io);

        $this->generateLienSocial($io);

        $items = [
          ['code' => 'CEL', 'libelle' => 'Célibataire'],
          ['code' => 'MAR', 'libelle' => 'Marié.e'],
          ['code' => 'PAC', 'libelle' => 'Pacsé.e'],
          ['code' => 'DIV', 'libelle' => 'Divorcé.e'],
          ['code' => 'VEU', 'libelle' => 'Veuf.ve'],
          ['code' => 'AUT', 'libelle' => 'Autre'],
        ];

        $classname = SituationFamilliale::class;
        $io->text("Téléchargement du référentiel 'Situation familliale'");
        foreach($items as $item) {
          $entity = $this->getEntityFromArray($item, $classname);
          unset($entity);
        }

        $items = [
          ['code' => 'CCPV', 'libelle' => 'CCPV'],
          ['code' => 'PVCI', 'libelle' => 'PVCI'],
          ['code' => 'COPJ', 'libelle' => 'COPJ'],
          ['code' => 'ORTS', 'libelle' => 'ORTS'],
          ['code' => 'RENV', 'libelle' => 'Renvoi'],
          ['code' => 'REFC', 'libelle' => 'Refus CRPC'],
        ];
        $classname = ModeConvocation::class;
        $io->text("Téléchargement du référentiel 'Mode de convocation'");
        foreach($items as $item) {
          $entity = $this->getEntityFromArray($item, $classname);
          unset($entity);
        }

        $items = [
          ['code' => '001', 'libelle' => 'ADM'],
          ['code' => '002', 'libelle' => 'ARUP'],
          ['code' => '003', 'libelle' => 'ASSO'],
          ['code' => '004', 'libelle' => 'AU'],
          ['code' => '005', 'libelle' => 'EURL'],
          ['code' => '006', 'libelle' => 'GEIE'],
          ['code' => '007', 'libelle' => 'GIC'],
          ['code' => '008', 'libelle' => 'GIE'],
          ['code' => '009', 'libelle' => 'INC'],
          ['code' => '010', 'libelle' => 'SA'],
          ['code' => '011', 'libelle' => 'SARL'],
          ['code' => '012', 'libelle' => 'SAS'],
          ['code' => '013', 'libelle' => 'SB'],
          ['code' => '014', 'libelle' => 'SC'],
          ['code' => '015', 'libelle' => 'SCA'],
          ['code' => '016', 'libelle' => 'SCI'],
          ['code' => '017', 'libelle' => 'SCP'],
          ['code' => '018', 'libelle' => 'SECA'],
          ['code' => '019', 'libelle' => 'SERL'],
          ['code' => '020', 'libelle' => 'SNC'],
          ['code' => '021', 'libelle' => 'SNP'],
          ['code' => '022', 'libelle' => 'SOS'],
          ['code' => '023', 'libelle' => 'SYND'],
          ['code' => '024', 'libelle' => 'EPIC'],
          ['code' => '025', 'libelle' => 'EIRL'],
        ];
        $classname = FormeJuridique::class;
        $io->text("Téléchargement du référentiel 'Forme juridique'");
        foreach($items as $item) {
          $entity = $this->getEntityFromArray($item, $classname);
          unset($entity);
        }

        $items = [
         ['code' => 'CMP', 'libelle' => 'Comparant'],
         ['code' => 'CMPSM', 'libelle' => 'Comparant assisté sans mandat'],
         ['code' => 'CMPAM', 'libelle' => 'Comparant assisté avec mandat'],
         ['code' => 'NCMP', 'libelle' => 'Non comparant'],
         ['code' => 'NCMPSM', 'libelle' => 'Non comparant représenté sans mandat'],
         ['code' => 'NCMPAM', 'libelle' => 'Non comparant représenté avec mandat'],
       ];
       $classname = ModeComparution::class;
       $io->text("Téléchargement du référentiel 'Mode de comparution'");
       foreach($items as $item) {
         $entity = $this->getEntityFromArray($item, $classname);
         unset($entity);
       }

       $io->success('Commande terminée avec succès');

       return Command::SUCCESS;
    }

    private function getEntityFromArray(array $item, string $classname): mixed {
      $repo = $this->em->getRepository($classname);
      $code=$item['code']??null;
      $libelle=$item['libelle']??null;
      $mnemo=$item['mnemo']??null;
      $famille=$item['options']['famille']??null;
      if(null!==$code) {
        $entity = $repo->findOneBy(['code' => $code]);
        if(null===$entity) {
          $entity = new $classname();
          $entity->setCode($code);
        }
        $entity->setLibelle($libelle);
        $entity->setMnemo($mnemo);
        $repo->save($entity, true);
      }
      return $entity;
    }
}
