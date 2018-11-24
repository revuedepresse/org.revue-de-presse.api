<?php

namespace App\Security;

use App\Member\Authentication\Authenticator;
use App\Member\MemberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use WTW\UserBundle\Repository\UserRepository;

class Auth0TokenAuthenticator extends TokenAuthenticator
{
    /**
     * @var UserRepository
     */
    public $userRepository;

    /**
     * @var Authenticator
     */
    public $authenticator;

    /**
     * @param Request $request
     * @return array|mixed|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCredentials(Request $request)
    {
        if (!$token = $request->headers->get('x-auth-admin-token')) {
            $token = null;
        }

        return ['token' => $token];
    }

    /**
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     * @return MemberInterface|null|UserInterface
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $apiKey = $credentials['token'];

        if (null === $apiKey) {
            return null;
        }

        try {
            $tokenInfo = $this->authenticator->authenticate($apiKey);
        } catch (\Exception $exception) {
            return null;
        }

        $member = $this->userRepository->findByAuthenticationToken($tokenInfo);

        if ($member instanceof MemberInterface) {
            return $member;
        }
    }
}
