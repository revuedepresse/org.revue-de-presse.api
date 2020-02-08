<?php

namespace App\Security;

use App\Member\Authentication\Authenticator;
use App\Member\MemberInterface;
use App\Member\Repository\AuthenticationTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use WTW\UserBundle\Repository\MemberRepository;

abstract class Auth0TokenAuthenticator extends TokenAuthenticator
{
    /**
     * @var MemberRepository
     */
    public $userRepository;

    /**
     * @var AuthenticationTokenRepository
     */
    public $authenticationTokenRepository;

    /**
     * @var Authenticator
     */
    public $authenticator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @param Request $request
     * @return array|mixed|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCredentials(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            $token = $this->authenticationTokenRepository->findOneBy([]);

            return ['token_info' => ['sub' => $token->getToken()]];
        }

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
        $apiKey = null;
        if (array_key_exists('token', $credentials)) {
            $apiKey = $credentials['token'];
        }

        if (is_null($apiKey) && !array_key_exists('token_info', $credentials)) {
            return null;
        }

        $tokenInfo = $this->decodeTokenInfo($credentials, $apiKey);
        if (is_null($tokenInfo)) {
            return null;
        }

        $member = $this->userRepository->findByAuthenticationToken($tokenInfo);

        if ($member instanceof MemberInterface) {
            return $member;
        }
    }

    /**
     * @param $credentials
     * @param $apiKey
     * @return array|null
     */
    private function decodeTokenInfo($credentials, $apiKey)
    {
        if (!array_key_exists('token_info', $credentials)) {
            try {
                return $this->authenticator->authenticate($apiKey);
            } catch (\Exception $exception) {
                $this->logger->info($exception->getMessage());

                return null;
            }
        }

        return $credentials['token_info'];
    }
}
