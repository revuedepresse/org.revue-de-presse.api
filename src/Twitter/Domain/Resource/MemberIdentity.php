<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Resource;

class MemberIdentity
{
    private string $screenName;

    private string $id;

    public function __construct(string $screenName, string $id)
    {
        $this->screenName = $screenName;
        $this->id = $id;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'screen_name' => $this->screenName,
        ];
    }
}