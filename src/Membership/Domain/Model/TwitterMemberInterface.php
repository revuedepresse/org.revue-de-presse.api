<?php

namespace App\Membership\Domain\Model;

interface TwitterMemberInterface extends MemberInterface
{
    /**
     * @return string
     */
    public function twitterId(): ?string;

    /**
     * @param string $twitterId
     * @return TwitterMemberInterface
     */
    public function setTwitterID(string $twitterId): MemberInterface;

    /**
     * @return bool
     */
    public function hasTwitterId(): bool;

    /**
     * @param $twitterUsername
     * @return TwitterMemberInterface
     */
    public function setTwitterScreenName(string $twitterUsername): MemberInterface;

    /**
     * @return string
     */
    public function twitterScreenName(): string;
}