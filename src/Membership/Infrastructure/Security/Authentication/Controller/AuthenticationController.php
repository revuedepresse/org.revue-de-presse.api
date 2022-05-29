<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Security\Authentication\Controller;

use App\Membership\Infrastructure\Security\Authentication\Authenticator;
use Auth0\SDK\Exception\CoreException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationController
{
    /**
     * @var Authenticator
     */
    public Authenticator $authenticator;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request)
    {
        $token = $request->get('token');

        try {
            $tokenInfo = $this->authenticator->authenticate($token);
        } catch (CoreException $exception) {
            return new JsonResponse('An unexpected error has occurred.', 501);
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), 403);
        }

        return new JsonResponse($tokenInfo, 200);
    }
}
