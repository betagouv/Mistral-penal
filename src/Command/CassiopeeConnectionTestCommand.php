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

use App\Service\Cassiopee\LoginService;
use App\Utils\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



#[AsCommand(name: "app:cassiopee:test-connection")]
class CassiopeeConnectionTestCommand extends Command
{

    public function __construct(private LoginService $loginService) {
        parent::__construct();
    }

    protected function configure(): void {
        $this->addOption('username', 'u', InputOption::VALUE_REQUIRED);
        $this->addOption('password', 'p', InputOption::VALUE_REQUIRED);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $output->writeln("BASE URL = " . Env::get('CASSIOPEE_BASE_URL'));
        $output->writeln("DNS PROXY = " . Env::get('CASSIOPEE_DNS_PROXY'));

        $username = $input->getOption('username');
        $password = $input->getOption('password');

        $loginInfos = $this->loginService->login($username, $password);

        $output->writeln(print_r($loginInfos, true));

        return Command::SUCCESS;
    }
}