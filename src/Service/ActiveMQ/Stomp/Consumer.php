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

use Stomp\Broker\ActiveMq\Mode\DurableSubscription as StompSubscription;
use Stomp\Client as StompClient;
use Stomp\Transport\Frame as StompFrame;

class Consumer extends AbstractUser {

    /** @var StompSubscription */
    private $subscription;
    /** @var string|null */
    private $lastMessage;
    /** @var string */
    private $errmsg;

    public function setErrmsg(string $errmsg): self {
        $this->errmsg = $errmsg;

        return $this;
    }

    public function getErrmsg(): string {
        return $this->errmsg;
    }

    /**
     * @return StompClient
     */
    public function getClient(): StompClient {
        /** @var bool $configRequired */
        $configRequired = (null === $this->client);
        $client = parent::getClient();
        if(true === $configRequired) {
            $client->getConnection()->setReadTimeout(1);
            $client->setClientId($this->getClientId());
        }
        return $client;
    }

    public function __toString(): string {
        $data = [
            'url' => $this->getUrl(),
            'address' => $this->getAddress(),
            'queue' => $this->getQueue(),
        ];

        return str_replace("\/",'/',json_encode($data));
    }
    /**
     * @return StompSubscription|null
     */
    public function getSubscription(): ?StompSubscription {
        if(null === $this->subscription) {
            try {
                $this->subscription = new StompSubscription(
                    $this->getClient(),
                    $this->getAddress(),
                    null,
                    'client',
                    $this->getSubscriptionId()
                );

                $this->subscription->activate();
            }
            catch(\Exception $e) {
                $this->subscription = null;
                $this->setErrmsg($e->getMessage());
            }
        }
        return $this->subscription;
    }

    public function resetError() {
        $this->errmsg = null;
    }

    public function hasError(): bool {
        return (null !== $this->errmsg);
    }

    public function getError(): string {
        return $this->errmsg;
    }

    /**
     * Récupération du premier élément avec conservation dans la file d'attente
     *
     * @return StompFrame|null
     */
    public function read(): ?StompFrame {

        $this->resetError();

        /** @var StompSubscription|null */
        $subscription = $this->getSubscription();
        if(null === $subscription) { return null; }

        /** @var StompFrame|bool $msg */
        $msg = $subscription->read();
        $this->lastMessage = (false !== $msg) ? $msg : null;
        $this->lastData = (false !== $msg) ? $this->reversePrepareData($this->lastMessage) : null;
        return $this->lastMessage;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->lastData;
    }

    public function getObjectData() {
        if(is_array($this->lastData)) {
            return json_decode(json_encode($this->lastData));
        }
        return null;
    }

    /**
     * Récupération du premier élément avec suppression dans la file d'attente
     *
     * @return StompFrame|null
     */
    public function readAndAck(): ?StompFrame {
        $msg = $this->read();
        $this->ack();
        return $msg;
    }

    /**
     * @return bool
     */
    public function ack(): bool {
        if(null !== $this->lastMessage) {
            $this->getSubscription()->ack($this->lastMessage);
            $this->lastMessage = null;
            return true;
        }
        return false;
    }

}
