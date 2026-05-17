<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;

final class BasicClientCredentialsExtractor
{
    public function __construct(private readonly EntityRepository $memberRepository)
    {
    }

    public function extract(Request $request): Member
    {
        $header = (string) $request->headers->get('Authorization', '');

        if (!str_starts_with($header, 'Basic ')) {
            throw new InvalidClientCredentialsException('Missing or non-Basic Authorization header');
        }

        $payload = base64_decode(substr($header, 6), true);
        if ($payload === false || !str_contains($payload, ':')) {
            throw new InvalidClientCredentialsException('Malformed Basic credentials');
        }

        [, $submittedSecret] = explode(':', $payload, 2);
        if ($submittedSecret === '') {
            throw new InvalidClientCredentialsException('Empty client secret');
        }

        $candidates = $this->memberRepository->findBy(['enabled' => true]);

        $match = null;
        foreach ($candidates as $candidate) {
            if ($candidate->apiKey !== null && hash_equals($candidate->apiKey, $submittedSecret)) {
                $match = $candidate;
            }
        }

        if ($match === null) {
            throw new InvalidClientCredentialsException('Invalid client credentials');
        }

        return $match;
    }
}
