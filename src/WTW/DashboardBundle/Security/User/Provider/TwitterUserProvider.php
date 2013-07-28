<?php

namespace WTW\DashboardBundle\Security\User\Provider;

use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
    Symfony\Component\Security\Core\Exception\UnsupportedUserException,
    Symfony\Component\Security\Core\Exception\AuthenticationException,
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
    protected $twitterOauth;

    /**
     * @var \FOS\UserBundle\Doctrine\UserManager
     */
    protected $userManager;

    /**
     * @var \Symfony\Component\Validator\Validator
     */
    protected $validator;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $session;

    /**
     * @param \FOS\UserBundle\Doctrine\UserManager $userManager
     */
    public function setUserManager($userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @return \FOS\UserBundle\Doctrine\UserManager
     */
    public function getUserManager()
    {
        return $this->userManager;
    }

    /**
     * @param \Symfony\Component\Validator\Validator $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return \Symfony\Component\Validator\Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    protected $twitterApiVersion;

    /**
     * @param \Twitter $twitterOauth
     */
    public function setTwitterOauth($twitterOauth)
    {
        $this->twitterOauth = $twitterOauth;
    }

    /**
     * @return \Twitter
     */
    public function getTwitterOauth()
    {
        return $this->twitterOauth;
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

       $this->twitterOauth->setOAuthToken($this->session->get('access_token'), $this->session->get('access_token_secret'));

       try {
           $info = $this->twitterOauth->get('account/verify_credentials');
       } catch (\Exception $e) {
           $info = null;
       }

       if (!empty($info)) {
           if (isset($info->errors) && is_array($info->errors) && count($info->errors)) {
               throw new AuthenticationException($info->errors[0]->message, $info->errors[0]->code);
           }

           if (empty($user)) {
               $user = $this->userManager->createUser();
               $user->setEnabled(false);
               $user->setLocked(false);
           }

           $username = $info->screen_name;

           $user->setTwitterID($info->id);
           $user->setTwitterUsername($username);

           $email = $user->getEmail();
           if (is_null($email) || (strlen(trim($email)) === 0)) {
               $user->setEmail($username . '@twitter.com');
           }

           if (null === $user->getUsername()) {
               $user->setUsername($username);
           }

           $user->setFullName($info->name);
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