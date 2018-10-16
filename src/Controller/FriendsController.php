<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserRelationship;
use App\Repository\UserRelationshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/friends", name="friends.")
 * Class FriendsController
 * @package App\Controller
 */
class FriendsController extends AbstractController
{
    /**
     * @Route("/friends", name="friends")
     */
    public function index()
    {
        return $this->render('friends/index.html.twig', [
            'controller_name' => 'FriendsController',
        ]);
    }

    /**
     * @Route("/{id}/add", name="addAsFriend")
     * @param User $user
     * @param UserRelationshipRepository $userRelationshipRepository
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function addAsFriend(User $user, UserRelationshipRepository $userRelationshipRepository, EntityManagerInterface $entityManager)
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // NOTE: check if the user is logged in
        if (!$currentUser instanceof UserInterface) {
            return $this->redirect($this->generateUrl('login'));
        }
        // NOTE: the user cannot add himself
        if ($this->getUser() === $user) {
            return $this->json([
                'type' => 'error',
                'message' => 'You cannot add yourself as a friend! that\'s weird man'
            ]);
        }

        // Check if the users are already friends
        $relationship = $userRelationshipRepository->findByRelatingUserAndRelatedUser(
            $currentUser->getId(),
            $user->getId()
        );

        if (!$relationship) {
            // i need to two records in the db
            // if nothing in the db then add the record
            $friendship = new UserRelationship();
            $friendship->setRelatingUser($currentUser);
            $friendship->setRelatedUser($user);
            // using some constants from the userRelationship class just in case i wanted to change them later
            //  also to take advantage of phpstorm autocomplete
            $friendship->setType(UserRelationship::PENDING);

            // using some constants from the userRelationship class just in case i wanted to change them later
            //  also to take advantage of phpstorm autocomplete

            $entityManager->persist($friendship);

            $entityManager->flush();
        }

        dump($relationship); die;
    }

    /**
     * @Route("/pending", name="pending")
     * @param UserRelationshipRepository $userRelationshipRepository
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pending(UserRelationshipRepository $userRelationshipRepository)
    {
        $this->checkIfUserLoggedIn();
        // check if the user logged in
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $result = $userRelationshipRepository->findUsersWithTypePending($currentUser->getId());

        return $this->render('friends/pending.html.twig', [
            'pending' => $result,
        ]);
    }

    /**
     * @Route("/{id}/approve", name="approveRequest")
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function approveRequest(User $user)
    {
        // check if the given user is actually a user or not
        if (!$user instanceof UserInterface) {
            return $this->redirect($this->generateUrl('login'));
        }

        
    }

    /**
     * Check if the user is logged in, if not will redirect him to the login page
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function checkIfUserLoggedIn()
    {
        if (!$this->getUser() instanceof UserInterface) {
            return $this->redirect($this->generateUrl('login'));
        }
    }
}
