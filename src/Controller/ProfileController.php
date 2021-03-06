<?php

    namespace App\Controller;

    use App\Entity\Post;
    use App\Entity\User;
    use App\Form\UserProfileType;
    use App\Repository\PostRepository;
    use App\Repository\UserRelationshipRepository;
    use App\Repository\UserRepository;
    use App\Services\AvatarManager;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Annotation\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use Symfony\Component\Security\Core\User\UserInterface;
    use App\Services\FileManager;

    /**
     * @Route("/profile", name="profile.")
     * Class ProfileController
     * @package App\Controller
     */
    class ProfileController extends AbstractController
    {
        /** @var AvatarManager $_avatarManager */
        private $_avatarManager;

        public function __construct(AvatarManager $avatarManager)
        {
            $this->_avatarManager = $avatarManager;
        }

        /**
         * @Route("/user/{username?}", name="userProfile", options={"expose"=true})
         * @param $username
         * @param UserRepository $userRepository
         * @param UserRelationshipRepository $userRelationshipRepository
         * @param PostRepository $postRepository
         * @param EntityManagerInterface $entityManager
         * @return \Symfony\Component\HttpFoundation\Response
         * @throws \Doctrine\ORM\NonUniqueResultException
         */
        public function userProfile($username, UserRepository $userRepository, UserRelationshipRepository $userRelationshipRepository, PostRepository $postRepository, EntityManagerInterface $entityManager)
        {
            // server-side rendering no need to worry about sensitive information getting out!
            // If the user is not logged in he will be redirected to somewhere else!
            // TODO: redirect the user to the login page if note authenticated!
            // No need to redirect, the guest should be able to access profiles but no do ANYTHING, just look
//            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            // TODO: Increment the views_counter for the user if someone is seeing this page
            // TODO: test this stuff further more, just to make sure everything is working fine!
            $user = null;
            $relationship = null;
            // if not logged in redirect him to the sing in page
            if (!$this->isUserSignedIn() && is_null($username)) {
                return $this->redirect(
                    $this->generateUrl('login'), 302
                );
            }
            $security = [];

            // if no username, display the current logged in user
            if (!$username) {
                /** @var User $user */
                $user = $this->getUser();

                if ($user->getLastPasswordReset()) {
                    $security[] = [
                        'type'      => 'passwordReset',
                        'icon'      => 'bell',
                        'state'     => 'info',
                        'message'   => 'Your password was last reset on the ' . $user->getLastPasswordReset()->format('Y-d-m h:m')
                    ];
                }

            } else {
                $user = $userRepository->findOneBy([
                    'username' => $username
                ]);
                // This function is only available for logged in users
                // check if the current user is friends with the $user
                if ($this->isUserSignedIn()) {
                    $relationship = $userRelationshipRepository->findOneFriendById(
                        $this->getUser()->getId(),
                        $user->getId()
                    );
                    if ($this->isUserSignedIn()) {
                        $recentlyAddedFriends = $userRelationshipRepository->findFriendsWithLimitById($user->getId(), 5);
                    }
                    $friends = $userRelationshipRepository->findUsersWithTypeFriend($user->getId());
                    $profileLink = $this->generateUrl('profile.userProfile', ['username' => $user->getUsername()], UrlGeneratorInterface::ABSOLUTE_URL);
                }

            }

            if (!($user === $this->getUser())) {
                // INC the counter
                // TODO: only increment if not from the same IP address
                $views_counter = $user->getViewsCounter();
                $user->setViewsCounter(++$views_counter);

                $entityManager->persist($user);
                $entityManager->flush();
            }

            // TODO: Get the last five friends the user added
            // TODO: what am gonna do now needs to be moved up i think


            return $this->render('profile/userProfile.html.twig', [
                'profile' => $user,
                'friends' => $friends ?? null, // i think this would be null anyways
                'isFriend' => (bool)$relationship,
                'recentFriends' => $recentlyAddedFriends ?? null,
//                'recentPosts' => $postRepository->findRecentlyPublishedPostsWithUserIdWithLimit($user->getId(), 10),
                'profileLink'   => $profileLink ?? null,
                'security'      => $security
            ]);
        }

        /**
         * @Route("/edit/", name="edit")
         * @Security("is_granted('ROLE_USER')")
         * @param Request $request
         * @param EntityManagerInterface $manager
         * @return \Symfony\Component\HttpFoundation\Response
         * @throws \Exception
         * @throws \Psr\Container\ContainerExceptionInterface
         * @throws \Psr\Container\NotFoundExceptionInterface
         */
        public function edit(Request $request, EntityManagerInterface $manager)
        {
            // TODO: check if the user has enough permissions to change information
            // the logged in user should be able to change his info
            /** @var User $user */
            $user = $this->getUser();
            if (!$user instanceof UserInterface) {
                return false;
            }
            $form = $this->createForm(UserProfileType::class, $user, [
                'action' => $this->generateUrl('profile.edit')
            ]);

            $oldFilename = $user->getAvatar();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $request->isXmlHttpRequest()) {
                // TODO: Change this to a transaction maybe? not really sure why would this fail!
                // get the file
                /** @var UploadedFile $file */
                $file = $request->files->get('file');

                // check if i have a file if "don't" then just ignore this section for the avatar
                if ($file) {

                    // upload the file and return the filename
                    $filenameWithUploadPath = $this->_avatarManager->uploadAvatar($file, $oldFilename);
                    // set the new name to the user
                    $user->setAvatar($filenameWithUploadPath);
                }

                // upload the profile picture that we got from the request and save the new name to the database
                $manager->persist($user);
                $manager->flush();

                return $this->json([
                    'type' => 'success',
                    'message' => 'your profile was updated!'
                ]);
            }
            return $this->render('profile/edit.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        /**
         * @Route("/author", name="author")
         * @param Request $request
         * @return \Symfony\Component\HttpFoundation\Response
         */
        public function author(Request $request, Post $post)
        {
            return $this->render('profile/author.html.twig', [
                // TODO: this will return the entire username i might need to send just the things i need
                'author' => $post->getUser()
            ]);
        }

        public function profile(User $user)
        {
            return $this->render('profile/profile.html.twig', [
                'profile' => $user,
            ]);
        }

        public function generateUniqueName()
        {
            return md5(uniqid());
        }

        public function getUploadsDir()
        {
            return $this->getParameter('avatars_dir');
        }


        public function getUploadsDirWithoutRoot()
        {
            return $this->getParameter('avatars_dir_no_root');
        }

        /**
         * Checks if the user is signed in
         * NOTE: this might need to change, i don't feel comfortable with this logic.
         * @return bool
         */
        private function isUserSignedIn()
        {
            return $this->getUser() instanceof UserInterface;
        }


    }
