<?php

namespace App\Services;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class MessageManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var Security
     */
    private $security;
    /**
     * @var ConversationRepository
     */
    private $conversationRepository;

    public function __construct(EntityManagerInterface $entityManager,
                                UserRepository $userRepository,
                                Security $security,
                                ConversationRepository $conversationRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * The sender is current logged in user, that's why it was omitted
     * @param string $message
     * @param string $recipient
     * @param int $conversation_id
     * @param string $sender
     * @throws \Exception
     */
    public function saveMessage(string $message, string $recipient, int $conversation_id, string $sender)
    {
        if (empty($sender) || empty($recipient)) {
            throw new \Exception("Both the sender and the recipient are required!");
        }
        // TODO: use queues in this case
        // NOTE: the sender is being set in the MessageListener
        // TODO get the recipient object
        $recipientObject = $this->userRepository->findOneBy([
            'username' => $recipient
        ]);
        // get the conversation object
        $conversation = $this->conversationRepository->find($conversation_id);

        $senderObject = $this->userRepository->findOneBy([
            'username' => $sender
        ]);

        $messageObject = new Message();

        $messageObject->setMessage($message);
        $messageObject->setReciepent($recipientObject);
        $messageObject->setSender($senderObject);

        // set the conversation_id
        $messageObject->setConversation($conversation);

        $this->entityManager->persist($messageObject);

        $recipientObject->addReceivedMessage($messageObject);
        $senderObject->addSentMessage($messageObject);

        $this->entityManager->flush();
    }

}