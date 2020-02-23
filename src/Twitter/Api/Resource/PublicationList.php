<?php
declare(strict_types=1);

namespace App\Twitter\Api\Resource;

class PublicationList
{
    private string $name;

    /**
     * @var string
     */
    private string $id;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }
}