<?php

namespace WTW\DashboardBundle\Security\User\Provider;

use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
    Symfony\Component\Security\Core\Exception\UnsupportedUserException,
    Symfony\Component\Security\Core\User\UserProviderInterface,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\HttpFoundation\Session\Session,
    Symfony\Component\Validator\Validator;
use \TwitterOAuth;

/**
 * Class TwitterUserProvider
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class TwitterUserProvider implements UserProviderInterface
{
   /**
    * @var \Twitter
    */
   protected $twitter_oauth;
   protected $userManager;
   protected $validator;
   protected $session;

   public function __construct(
       \TwitterOAuth $twitter_oauth,
       UserManager $userManager,
       Validator $validator,
       Session $session)
   {
       $this->twitter_oauth = $twitter_oauth;
       $this->userManager = $userManager;
       $this->validator = $validator;
       $this->session = $session;
   }

   public function supportsClass($class)
   {
       return $this->userManager->supportsClass($class);
   }

   public function findUserByTwitterUsername($twitterUsername)
   {
       return $this->userManager->findUserBy(array('twitter_username' => $twitterUsername));
   }

   public function loadUserByUsername($username)
   {
       $user = $this->findUserByTwitterUsername($username);

       $this->twitter_oauth->setOAuthToken($this->session->get('access_token'), $this->session->get('access_token_secret'));

       try {
           $info = $this->twitter_oauth->get('account/verify_credentials');
       } catch (Exception $e) {
           $info = null;
       }

       if (!empty($info)) {
           if (empty($user)) {
               $user = $this->userManager->createUser();
               $user->setEnabled(true);
               $user->setPassword('');
           }

           $username = $info->screen_name;

           $user->setTwitterID($info->id);
           $user->setTwitterUsername($username);
           $user->setEmail('');
           $user->setFirstname($info->name);

           $this->userManager->updateUser($user);
       }

       if (empty($user)) {
           throw new UsernameNotFoundException('The user is not authenticated on twitter');
       }

       return $user;
   }

   public function refreshUser(UserInterface $user)
   {
       if (!$this->supportsClass(get_class($user)) || !$user->getTwitterID()) {
           throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
       }

       return $this->loadUserByUsername($user->getTwitterID());
   }
}