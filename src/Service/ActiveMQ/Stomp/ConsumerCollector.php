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
namespace App\Service\ActiveMQ\Stomp;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ConsumerCollector {

    private $items=[];
    /** @var Consumer|null */
    private $currentConsumer;

    public function disconnect() {
        if(null !== $this->currentConsumer) {
            $this->currentConsumer->disconnect();
            $this->currentConsumer = null;
        }
    }

    public function __construct(ContainerInterface $container) {
        /** @var string[] $ids */
        $serviceIds = $container->getServiceIds();
        foreach($serviceIds as $serviceId) {
            if(preg_match("/amq[.]consumer[.]/i", $serviceId)) {
                $consumer = $container->get($serviceId);
                if($consumer instanceof Consumer) {
                    $this->items[$serviceId]=$consumer;
                }
            }
        }
    }

    public function getConsumer(string $filter=null): ?Consumer {
        foreach($this->items as $serviceId => $consumer) {
            if((null !== $filter) &&(!preg_match("/$filter/i", $serviceId))) {
                continue;
            }
            if(true === $consumer->isConnected()) {
                $this->currentConsumer = $consumer;
                return $consumer;
            }
        }
        return null;
    }

}
