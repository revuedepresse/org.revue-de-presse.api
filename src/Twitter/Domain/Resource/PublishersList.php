<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Resource;

class PublishersList
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

    public function name(): string
    {
        return $this->name;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
           'id' => $this->id,
           'name' => $this->name
        ];
    }
}