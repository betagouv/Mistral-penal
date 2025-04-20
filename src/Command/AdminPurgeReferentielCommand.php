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

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin:purge-des-referentiels',
    description: "Fonction dédiée à la purge des référentiels (élimination des doublons éventuels)",
)]
class AdminPurgeReferentielCommand extends Command 
{
    public function __construct(private EntityManagerInterface $em) {
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

        $io->title('Lancement de la commande de purge des référentiels');

        $this->purge();

        $io->success('La purge de la base de donnée a été effectuée avec succès');

        return Command::SUCCESS;
    }

    public static function make_sql(string $table): string {
        $sql = "
        DELETE
        FROM webapp.$table
        WHERE id NOT IN (
          SELECT h.id FROM (
            SELECT a.libelle,MIN(a.id) id
            FROM webapp.$table a
            GROUP BY a.libelle
          ) h
        );";

        return $sql;
    }
    public function purge(): void {
        $conn = $this->em->getConnection();

        $sqls = [
          self::make_sql("antecedent_judiciaire"),
          self::make_sql("categorie_penale"),
          self::make_sql("decision_prevention"),
          self::make_sql("forme_juridique"),
          self::make_sql("modalite_participation"),
          self::make_sql("lien_juridique"),
          self::make_sql("lien_social"),
          self::make_sql("mode_convocation"),
          self::make_sql("mode_poursuite"),
          self::make_sql("modulation_peine"),
          self::make_sql("nationalite"),
          self::make_sql("nature_jugement"),
          self::make_sql("renvoi_motif"),
          self::make_sql("sans_domicile"),
          self::make_sql("situation_familliale"),
        ];
        foreach($sqls as $sql) {
          $conn->exec($sql);
        }
    }
}
