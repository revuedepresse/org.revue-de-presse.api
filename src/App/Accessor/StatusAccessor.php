<?php

namespace App\Accessor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;

class StatusAccessor
{
    /**
     * @var EntityManager
     */
    public $entityManager;

    /**
     * @param $endpoint
     * @return array|mixed|null|\stdClass
     */
    public function contactEndpoint($endpoint)
    {
        if (strpos($endpoint, '/users/show.json') !== false) {
            $screenName = explode('=', parse_url($endpoint)['query'])[1];

            return $this->getResponseForMemberWithScreenName($screenName);
        }

        if (strpos($endpoint, '/rate_limit_status.json') !== false) {
            return $this->getRateLimits();
        }

        if (strpos($endpoint, '/user_timeline.json') !== false) {
            $queryParameters = explode('&', parse_url($endpoint)['query']);
            $queryParameters = array_map(function ($queryParameter) {
                $parts = explode('=', $queryParameter);
                return [
                    'key' => $parts[0],
                    'value' => $parts[1]
                ];
            }, $queryParameters);
            $filteredQueryParameters = array_filter($queryParameters, function ($queryParameter) {
                return $queryParameter['key'] == 'screen_name';
            });
            $screenName = array_pop($filteredQueryParameters)['value'];

            return $this->getTimelineStatusForMemberWithScreenName($screenName);
        }
    }

    /**
     * @param $screenName
     * @return mixed
     */
    private function getResponseForMemberWithScreenName(string $screenName): \stdClass
    {
        return json_decode(
            $this->entityManager->getRepository(ArchivedStatus::class)
                ->findOneBy(['screenName' => $screenName])->getApiDocument())
            ->user;
    }

    /**
     * @param $screenName
     * @return array
     */
    private function getTimelineStatusForMemberWithScreenName(string $screenName): array
    {
        $queryBuilder = $this->entityManager->getRepository(ArchivedStatus::class)
            ->createQueryBuilder('s')
            ->andWhere('s.screenName = :screen_name')
            ->addOrderBy('s.createdAt', 'ASC')
            ->groupBy('s.statusId')
            ->setMaxResults(200);

        $queryBuilder->setParameter('screen_name', $screenName);

        $statuses = $queryBuilder->getQuery()->getResult();

        $statusesCollection = new ArrayCollection($statuses);

        return $statusesCollection->map(
            function (ArchivedStatus $status) {
                return json_decode($status->getApiDocument());
            }
        )->toArray();
    }

    /**
     * @return \stdClass
     */
    private function getRateLimits(): \stdClass
    {
        return json_decode(unserialize(base64_decode('czoyMzkzOiJ7InJhdGVfbGltaXRfY29udGV4dCI6eyJhY2Nlc3NfdG9rZW4iOiIxNTEyMzQyNi1YR2lnUzZUZUlxN240TVUzTnRNZVpPeGh3Y05aNkJBdGVwOXQ2S3NFSiJ9LCJyZXNvdXJjZXMiOnsibGlzdHMiOnsiXC9saXN0c1wvbGlzdCI6eyJsaW1pdCI6MTUsInJlbWFpbmluZyI6MTUsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC9saXN0c1wvbWVtYmVyc2hpcHMiOnsibGltaXQiOjc1LCJyZW1haW5pbmciOjc1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvbGlzdHNcL3N1YnNjcmliZXJzXC9zaG93Ijp7ImxpbWl0IjoxNSwicmVtYWluaW5nIjoxNSwicmVzZXQiOjE1MjgwMzUxNDZ9LCJcL2xpc3RzXC9tZW1iZXJzIjp7ImxpbWl0Ijo5MDAsInJlbWFpbmluZyI6OTAwLCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvbGlzdHNcL3N1YnNjcmlwdGlvbnMiOnsibGltaXQiOjE1LCJyZW1haW5pbmciOjE1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvbGlzdHNcL3Nob3ciOnsibGltaXQiOjc1LCJyZW1haW5pbmciOjc1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvbGlzdHNcL293bmVyc2hpcHMiOnsibGltaXQiOjE1LCJyZW1haW5pbmciOjE1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvbGlzdHNcL3N1YnNjcmliZXJzIjp7ImxpbWl0IjoxODAsInJlbWFpbmluZyI6MTgwLCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvbGlzdHNcL21lbWJlcnNcL3Nob3ciOnsibGltaXQiOjE1LCJyZW1haW5pbmciOjE1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvbGlzdHNcL3N0YXR1c2VzIjp7ImxpbWl0Ijo5MDAsInJlbWFpbmluZyI6OTAwLCJyZXNldCI6MTUyODAzNTE0Nn19LCJ1c2VycyI6eyJcL3VzZXJzXC9yZXBvcnRfc3BhbSI6eyJsaW1pdCI6MTUsInJlbWFpbmluZyI6MTUsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC91c2Vyc1wvY29udHJpYnV0b3JzXC9wZW5kaW5nIjp7ImxpbWl0IjoyMDAwLCJyZW1haW5pbmciOjIwMDAsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC91c2Vyc1wvc2hvd1wvOmlkIjp7ImxpbWl0Ijo5MDAsInJlbWFpbmluZyI6Nzg3LCJyZXNldCI6MTUyODAzNTA0N30sIlwvdXNlcnNcL3NlYXJjaCI6eyJsaW1pdCI6OTAwLCJyZW1haW5pbmciOjkwMCwicmVzZXQiOjE1MjgwMzUxNDZ9LCJcL3VzZXJzXC9zdWdnZXN0aW9uc1wvOnNsdWciOnsibGltaXQiOjE1LCJyZW1haW5pbmciOjE1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvdXNlcnNcL2NvbnRyaWJ1dGVlc1wvcGVuZGluZyI6eyJsaW1pdCI6MjAwLCJyZW1haW5pbmciOjIwMCwicmVzZXQiOjE1MjgwMzUxNDZ9LCJcL3VzZXJzXC9kZXJpdmVkX2luZm8iOnsibGltaXQiOjE1LCJyZW1haW5pbmciOjE1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvdXNlcnNcL3Byb2ZpbGVfYmFubmVyIjp7ImxpbWl0IjoxODAsInJlbWFpbmluZyI6MTgwLCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvdXNlcnNcL3N1Z2dlc3Rpb25zXC86c2x1Z1wvbWVtYmVycyI6eyJsaW1pdCI6MTUsInJlbWFpbmluZyI6MTUsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC91c2Vyc1wvbG9va3VwIjp7ImxpbWl0Ijo5MDAsInJlbWFpbmluZyI6OTAwLCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvdXNlcnNcL3N1Z2dlc3Rpb25zIjp7ImxpbWl0IjoxNSwicmVtYWluaW5nIjoxNSwicmVzZXQiOjE1MjgwMzUxNDZ9fSwic3RhdHVzZXMiOnsiXC9zdGF0dXNlc1wvcmV0d2VldGVyc1wvaWRzIjp7ImxpbWl0Ijo3NSwicmVtYWluaW5nIjo3NSwicmVzZXQiOjE1MjgwMzUxNDZ9LCJcL3N0YXR1c2VzXC9yZXR3ZWV0c19vZl9tZSI6eyJsaW1pdCI6NzUsInJlbWFpbmluZyI6NzUsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC9zdGF0dXNlc1wvaG9tZV90aW1lbGluZSI6eyJsaW1pdCI6MTUsInJlbWFpbmluZyI6MTUsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC9zdGF0dXNlc1wvc2hvd1wvOmlkIjp7ImxpbWl0Ijo5MDAsInJlbWFpbmluZyI6OTAwLCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvc3RhdHVzZXNcL3VzZXJfdGltZWxpbmUiOnsibGltaXQiOjkwMCwicmVtYWluaW5nIjo3MzUsInJlc2V0IjoxNTI4MDM1MDQ4fSwiXC9zdGF0dXNlc1wvZnJpZW5kcyI6eyJsaW1pdCI6MTUsInJlbWFpbmluZyI6MTUsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC9zdGF0dXNlc1wvcmV0d2VldHNcLzppZCI6eyJsaW1pdCI6NzUsInJlbWFpbmluZyI6NzUsInJlc2V0IjoxNTI4MDM1MTQ2fSwiXC9zdGF0dXNlc1wvbWVudGlvbnNfdGltZWxpbmUiOnsibGltaXQiOjc1LCJyZW1haW5pbmciOjc1LCJyZXNldCI6MTUyODAzNTE0Nn0sIlwvc3RhdHVzZXNcL29lbWJlZCI6eyJsaW1pdCI6MTgwLCJyZW1haW5pbmciOjE4MCwicmVzZXQiOjE1MjgwMzUxNDZ9LCJcL3N0YXR1c2VzXC9sb29rdXAiOnsibGltaXQiOjkwMCwicmVtYWluaW5nIjo5MDAsInJlc2V0IjoxNTI4MDM1MTQ2fX19fSI7')));
    }
}
