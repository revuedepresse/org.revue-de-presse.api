<?php

namespace App\Membership\Domain\Entity;

interface TwitterMemberInterface extends MemberInterface
{
    /**
     * @return string
     */
    public function getTwitterID(): ?string;

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
    public function setTwitterUsername(string $twitterUsername): MemberInterface;

    /**
     * @return string
     */
    public function getTwitterUsername(): string;
}