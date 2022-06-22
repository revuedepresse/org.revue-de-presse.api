<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Resource;

use Assert\Assert;

class MemberIdentity
{
    private string $screenName;

    private string $id;

    public function __construct(string $screenName, string $id)
    {
        Assert::that($id)
            ->numeric('Member id should be numeric.')
            ->notEmpty('Member id should not be empty.')
            ->all();

        Assert::that($screenName)
            ->notEmpty('Member screen name should not be empty.')
            ->all();

        $this->screenName = strtolower(trim($screenName));
        $this->id = strtolower(trim($id));
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