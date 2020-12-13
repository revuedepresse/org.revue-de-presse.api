<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Membership;

use App\Twitter\Infrastructure\Amqp\ResourceProcessor\MemberIdentityProcessorInterface;

trait MemberIdentityProcessorTrait
{
    /**
     * @var MemberIdentityProcessorInterface
     */
    private MemberIdentityProcessorInterface $memberIdentityProcessor;

    /**
     * @param MemberIdentityProcessorInterface $memberIdentityProcessor
     *
     * @return $this
     */
    public function setMemberIdentityProcessor(MemberIdentityProcessorInterface $memberIdentityProcessor): self
    {
        $this->memberIdentityProcessor = $memberIdentityProcessor;

        return $this;
    }
}