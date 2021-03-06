<?php
namespace App\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ConversationTopic implements TopicInterface {

    private $clients;
    private $clientManipulator;

    /**
     * The Security component doesn't seem to work, trying something from the docs instead
     * ConversationTopic constructor.
     * @param ClientManipulatorInterface $clientManipulator
     */
    public function __construct(ClientManipulatorInterface $clientManipulator)
    {
        $this->clients = new \SplObjectStorage();
        $this->clientManipulator = $clientManipulator;
    }

    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // store the newly connected client
        $this->clients->attach($connection);
//        $currentUser = $this->clientManipulator->getClient($connection);
        // send the message to all subscribers of this topic
//        $topic->broadcast([
//            'type' => 'user_joined',
//            // TODO: include the username
//            'username' => $currentUser->getUsername(), // this will failed when using localhost instead of 127.0.0.1
//            'msg' => $connection->resourceId . " has joined " . $topic->getId(),
//            'connectedClients' => $topic->count()
//        ]);
    }

    // recieve a disconnect
    public function onUnsubscribe (ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // remove the connection when not subscribed anymore
        // otherwise the counter will always go up
        $this->clients->detach($connection);
//        $topic->broadcast([
//            'msg' => $connection->resourceId . " has left " . $topic->getId(),
//            'connectedClients' => $topic->count()
//        ]);
    }

    // receive publish request for this topic
    // this looks like the place where to send to count of connected clients
    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
    {
        // need to check if the user connected before moving further
        $username = $request->getAttributes()->get('username');
        $user = $this->clientManipulator->findByUsername($topic, $username);
        if ($user) {
            $topic->broadcast(array_merge($event, ['inc' => true]), [], [$user['connection']->WAMP->sessionId]);
        }
    }

    // like RPC (Remote Procedure Call) will use to prefix the channel
    public function getName()
    {
        return 'conversation.topic';
    }
}