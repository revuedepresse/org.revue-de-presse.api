<?php

namespace App\QualityAssurance\Infrastructure\Console;

use DateTimeInterface;
use JsonException;

class Tweet implements TweetInterface
{
    public readonly int $id;
    public readonly string $hash;
    public readonly string $username;
    public readonly string $name;
    public readonly string $text;
    public readonly string $avatar;
    public readonly string $statusId;
    public readonly array $rawDocument;
    public readonly bool $isStarred;
    public readonly bool $isIndexed;
    public readonly DateTimeInterface $createdAt;
    public readonly DateTimeInterface $updatedAt;
    public readonly bool $isPublished;

    private bool $hasBeenDeleted = false;

    public function tweetId(): string
    {
        return $this->statusId;
    }

    public function rawDocument(): array
    {
        return $this->rawDocument;
    }

    public function __construct(
        int               $id,
        string            $hash,
        string            $username,
        string            $name,
        string            $avatar,
        string            $statusId,
        string            $rawDocument,
        DateTimeInterface $createdAt,
        DateTimeInterface $updatedAt,
        bool              $isStarred,
        bool              $isIndexed = false,
        bool              $isPublished = false,
        bool              $hasBeenDeleted = false
    )
    {
        try {
            $this->rawDocument = json_decode(
                $rawDocument,
                true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException|\Exception $e) {
            error_log($e);

            throw new \InvalidArgumentException($e, $e->getCode(), $e);
        }

        $this->id = $id;
        $this->hash = $hash;
        $this->username = $username;
        $this->name = $name;
        $this->avatar = $avatar;
        $this->statusId = $statusId;
        $this->hasBeenDeleted = $hasBeenDeleted;
        $this->isStarred = $isStarred;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->isPublished = $isPublished;
        $this->isIndexed = $isIndexed;

        if (array_key_exists('text', $this->rawDocument)) {
            $this->text = $this->rawDocument['text'];

            return;
        }

        $this->text = $this->rawDocument['full_text'];
    }

    public function overrideProperties(array $overrides = []): self
    {
        if (array_key_exists('raw_document', $overrides)) {
            return new self(
                    $this->id,
                    $this->hash,
                    $this->username,
                    $this->name,
                    $this->avatar,
                    $this->statusId,
                    $overrides['raw_document'],
                    $this->createdAt,
                    $this->updatedAt,
                    $this->isStarred,
                    $this->isIndexed,
                    $this->isPublished,
                    $this->hasBeenDeleted
                );
        }

        $rawDocument = $this->rawDocument;

        $avatarDataURI = $this->rawDocument['user']['profile_image_url_https'];
        $rawDocument['_profile_image_url_https'] = $avatarDataURI;

        if (array_key_exists('avatar_data_uri', $overrides)) {
            $rawDocument['base64_encoded_avatar'] = 'data:image/webp;base64,'.$overrides['avatar_data_uri'];
        }

        if (array_key_exists('extended_entities', $this->rawDocument)) {
            $mediaDataURI = $this->rawDocument['extended_entities']['media'][0]['media_url'];
            $rawDocument['_media_url'] = $mediaDataURI;

            if (array_key_exists('media_data_uri', $overrides)) {
                $rawDocument['base64_encoded_media'] = 'data:image/webp;base64,'.$overrides['media_data_uri'];
            }
        }

        return new self(
            $this->id,
            $this->hash,
            $this->username,
            $this->name,
            $avatarDataURI,
            $this->statusId,
            json_encode($rawDocument),
            $this->createdAt,
            $this->updatedAt,
            $this->isStarred,
            $this->isIndexed,
            $this->isPublished,
            $this->hasBeenDeleted
        );
    }

    public function createdAt(): DateTimeInterface {
        return $this->createdAt;
    }

    public function hasBeenDeleted(): bool
    {
        return $this->hasBeenDeleted;
    }

    public function markAsDeleted(): self
    {
        $this->hasBeenDeleted = true;

        return $this;
    }

    /**
     * @throws \App\QualityAssurance\Infrastructure\Console\MediaNotFoundException
     */
    public function smallMediaURL(): string
    {
        if (!isset($this->rawDocument['extended_entities']['media'][0]['media_url'])) {
            MediaNotFoundException::throws($this->tweetId());
        }

        return $this->rawDocument['extended_entities']['media'][0]['media_url'] . ':small';
    }
}