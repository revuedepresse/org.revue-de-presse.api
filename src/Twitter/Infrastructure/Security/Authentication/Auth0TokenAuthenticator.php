<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Security\Authentication;

use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Membership\Infrastructure\Security\Authentication\Authenticator;
use App\Membership\Infrastructure\Repository\AuthenticationTokenRepository;
use App\Membership\Domain\Entity\MemberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class Auth0TokenAuthenticator extends TokenAuthenticator
{
    use LoggerTrait;

    /**
     * @var AuthenticationTokenRepository
     */
    public AuthenticationTokenRepository $authenticationTokenRepository;

    /**
     * @var Authenticator
     */
    public Authenticator $authenticator;

    /**
     * @param Request $request
     *
     * @return array|mixed|null
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
        if (\array_key_exists('token', $credentials)) {
            $apiKey = $credentials['token'];
        }

        if ($apiKey === null && !\array_key_exists('token_info', $credentials)) {
            return null;
        }

        $tokenInfo = $this->decodeTokenInfo($credentials, $apiKey);
        if ($tokenInfo === null) {
            return null;
        }

        $member = $this->memberRepository->findByAuthenticationToken($tokenInfo);

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
        if (!\array_key_exists('token_info', $credentials)) {
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
