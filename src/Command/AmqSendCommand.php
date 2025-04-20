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

use App\Service\ActiveMQ\Stomp\Consumer;
use App\Service\ActiveMQ\Stomp\Producer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'amq:send',
    description: 'Envoi AMQ',
)]
class AmqSendCommand extends Command 
{
    public function __construct(
      private Producer $producer,
      private Consumer $consumer
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

        $io->title("Envoi des messages par le producteur");

        for($i=1;$i<100;$i++)
          $this->producer->send([
            'id' => 3,
            'text' => 'lorem ipsum',
          ]);

        $io->success("Envois terminés");

        while(null !== ($data = $this->consumer->readAndAck())) {
          $result = json_decode($data->getBody());
          dump($result);
        }

        die;


        /**
        $broker->subscribeQueue(Env::get('AMQ_QUEUE_SEND_DATA'));
        while(true) {
          $message = $broker->read();

          if(null !== $message) {
            if ($message['type'] === 'terminate') {
              $this->logNote('Received shutdown command');
              return Command::SUCCESS;
            }
            $this->logNote('Processed message: '.$message->getBody());
            $broker->ack($message);
          }
          else {
            $this->logNote('Aucun message envoyé');
            return Command::SUCCESS;
          }
        }
        */
    }
}
