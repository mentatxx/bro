<?php
namespace Bro\amqp;
/*
 * Copyright 2014 Alexey Petushkov
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Task exchange via AMQP (durable queue)
 */
class Amqp {
    // RabbitMQ host
    private $host;
    // Channel name
    private $prefix;
    /**
     * AMQP connection
     * @var \AMQPConnection
     */
    private $connection;
    private $queue;
    private $channel;
    private $exchange;
    
    public function __construct($host, $prefix) {
        $this->host = $host;
        $this->prefix = $prefix;
        // create connection
        $this->connection = new \AMQPConnection(array('host' => $host));
        $this->connection->connect();
        //
        $this->channel = new \AMQPChannel($this->connection);
        //
        $this->exchange = new \AMQPExchange($this->channel);
        $this->exchange->setFlags(AMQP_DURABLE);
        $this->exchange->setName('exch'.$this->prefix);
        $this->exchange->setType(AMQP_EX_TYPE_DIRECT);
        $this->exchange->declare();
        
        // create queue
        $this->queue = new \AMQPQueue($this->channel);
        $this->queue->setName($this->prefix);
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->declare();
        $this->queue->bind('exch'.$this->prefix, $this->prefix);
    }
    
    public function __destruct() {
        $this->connection->disconnect();
    }
    
    /**
     * Add task (as string) to queue
     * Dont forbid to encode objects before passing to this function
     * @param string $task
     */
    public function addTask($task) {
        $this->exchange->publish(json_encode($task), $this->prefix);
    }
    
    /**
     * Consume next task or False
     * @return \AMQPEnvelope
     */
    public function getTask () {
        return $this->queue->get();
    }
    
    /**
     * Peek next task or False
     * @return AMQPEnvelope
     */
    public function getTaskNoAck() {
        return $this->queue->get(0);
    }
    
    public function ack($envelope)
    {
        $this->queue->ack($envelope->getDeliveryTag());
    }
    
    public function nack($envelope)
    {
        $this->queue->nack($envelope->getDeliveryTag());
    }
}

