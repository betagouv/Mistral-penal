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

use App\Utils\Env;
use Stomp\Client as StompClient;
use Stomp\Transport\Frame as StompFrame;
use Stomp\Transport\Bytes as StompBytes;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Stomp\Util\IdGenerator;

abstract class AbstractUser {

    /** @var array|bool|float|int|string|null */
    private $treeBuilder;
    /** @var mixed|string */
    protected $protocol;
    /** @var mixed|string */
    protected $host;
    /** @var mixed|string */
    protected $port;
    /** @var mixed|string|null */
    protected $username;
    /** @var mixed|string|null */
    protected $password;
    /** @var StompClient|null */
    protected $client;
    /** @var mixed|string */
    protected $address;
    /** @var mixed|string */
    protected $queue;
    /** @var string|null */
    protected $localQueue;

    public function setLocalQueue(string $localQueue): self {
        $this->localQueue = $localQueue;

        return $this;
    }

    public function hasLocalQueue(): bool {
        return (null !== $this->localQueue);
    }

    public function getLocalQueue(): ?string {
        return $this->localQueue;
    }
    /**
     * AbstractUser constructor.
     */
    public function __construct() {
        $this->protocol     = Env::get('AMQ_PROTOCOL');
        $this->host         = Env::get('AMQ_HOST');
        $this->port         = Env::get('AMQ_PORT');
        $this->username     = Env::get('AMQ_LOGIN');
        $this->password     = Env::get('AMQ_PASSWORD');
        $this->address      = Env::get('AMQ_ADDRESS');
        $this->queue        = Env::get('AMQ_QUEUE');
    }

    /**
     * @return mixed
     */
    public function reversePrepareData(StompFrame $data) {
        try {
            /** @var string|null $reverseData */
            $reverseData = json_decode($data->body, true);
            if(true === is_array($reverseData)) {
                return $reverseData;
            }
            return unserialize($data->body);
        }
        catch(\Exception $e) {
            return null;
        }
    }

    public function setProtocol(string $protocol): self
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function getQueue(): string
    {
        if(false === $this->hasLocalQueue()) {
            $queue = $this->queue;
            $idGenerator = new IdGenerator();
            /** @var string $first */
            $first = (preg_match("/^(?<clientId>[^.]+)[.]/", $queue, $matches)) ? $matches['clientId'] : $queue;

            $second =(preg_match("/[.](?<subscriptionId>[^.]+)$/", $queue, $matches)) ? $matches['subscriptionId'] : $idGenerator->generateId();
            $this->setLocalQueue("$first.$second");
        }

        return $this->getLocalQueue();
    }

    /**
     * Récupération de l'identifiant de souscription
     *
     * Il s'agit de l'information situé après le "." de la file d'attente
     * Si non renseigné, alors il y'a génération d'un nombre aléatoire pour remplacer
     *
     * @return string
     */
    public function getSubscriptionId(): string
    {
        /** @var string */
        $queue = $this->getQueue();

        if(preg_match("/[.](?<subscriptionId>[^.]+)$/", $queue, $matches)) {
            return $matches['subscriptionId'];
        }
        $idGenerator = new IdGenerator();
        return $idGenerator->generateId();
    }

    public function getClientId(): string
    {
        /** @var string */
        $queue = $this->getQueue();

        if(preg_match("/^(?<clientId>[^.]+)[.]/", $queue, $matches)) {
            return $matches['clientId'];
        }
        else
            return $queue;

    }

    public function setQueue(string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }
    /**
     * @param $data
     * @return StompBytes
     */
    public function prepareData($data): StompBytes {
        /** @var string $output */
        $output = is_array($data) ? serialize($data) : $data;
        return new StompBytes($output);
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        $url = $this->protocol.'://'.$this->host.':'.$this->port;
        return $url;
    }
    /**
     * @return StompClient
     */
    public function getClient(): StompClient {
        if(null === $this->client) {
            /** @var string $url */
            $url = $this->protocol.'://'.$this->host.':'.$this->port;
            $this->client = new StompClient($url);
            if($this->username && $this->password)
                $this->client->setLogin($this->username,$this->password);
        }

        return $this->client;
    }

    /**
     * @return string
     */
    public function getAddress(): string {
        return $this->address;
    }

    public function disconnect() {
        try {
            $client = $this->getClient();

            if(true === $client->isConnected()) {
                $client->disconnect();
            }
        }
        catch(\Exception $e) {
        }
    }

    public function isConnected(): bool {
        try {
            $client = $this->getClient();

            if(false === $client->isConnected()) {
                $client->connect();
            }
        }
        catch(\Exception $e) {
            return false;
        }
        return $client ? $client->isConnected() : false;
    }
}
