<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Resource;

use Assert\Assert;

class MemberIdentity
{
    public const NOT_PERSISTED_MEMBER_NUMERIC_ID = '-1';

    private string $screenName;

    private string $id;

    public function __construct(string $screenName, string $id)
    {
        Assert::lazy()
            ->tryAll()
            ->that($id)
            ->numeric('Member id should be numeric.')
            ->notEmpty('Member id should not be empty.')
            ->that($screenName)
            ->notEmpty('Member screen name should not be empty.')
            ->verifyNow();

        $this->screenName = strtolower(trim($screenName));
        $this->id = strtolower(trim($id));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function screenName(): string
    {
        return strtolower($this->screenName);
    }

    public function isNumeric(): bool
    {
        return is_numeric($this->id) && intval($this->id) > 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'screen_name' => $this->screenName,
        ];
    }
}
