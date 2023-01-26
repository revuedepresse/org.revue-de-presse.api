<?php

namespace App\QualityAssurance\Infrastructure\Console;

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
    public readonly \DateTimeInterface $createdAt;
    public readonly \DateTimeInterface $updatedAt;
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
        int                $id,
        string             $hash,
        string             $username,
        string             $name,
        string             $text,
        string             $avatar,
        string             $statusId,
        string             $rawDocument,
        \DateTimeInterface $createdAt,
        \DateTimeInterface $updatedAt,
        bool               $isStarred,
        bool               $isIndexed = false,
        bool               $isPublished = false
    )
    {
        $this->id = $id;
        $this->hash = $hash;
        $this->username = $username;
        $this->name = $name;
        $this->text = str_replace('""', '"', $text);
        $this->avatar = $avatar;
        $this->statusId = $statusId;

        try {
            $this->rawDocument = json_decode(
                $rawDocument,
                true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            error_log($e);

            throw new \InvalidArgumentException($e, $e->getCode(), $e);
        } catch (\Exception $e) {
            error_log($e);

            throw new \InvalidArgumentException($e, $e->getCode(), $e);
        }

        $this->isStarred = $isStarred;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->isPublished = $isPublished;
        $this->isIndexed = $isIndexed;
    }

    public function overrideProperties(array $overrides = []): self
    {
        if (array_key_exists('raw_document', $overrides)) {
            return new self(
                    $this->id,
                    $this->hash,
                    $this->username,
                    $this->name,
                    $this->text,
                    $this->avatar,
                    $this->statusId,
                    $overrides['raw_document'],
                    $this->createdAt,
                    $this->updatedAt,
                    $this->isStarred,
                    $this->isIndexed,
                    $this->isPublished
                );
        }

        $rawDocument = $this->rawDocument;

        $avatarDataURI = $this->rawDocument['user']['profile_image_url_https'];
        $rawDocument['_profile_image_url_https'] = $avatarDataURI;

        if (array_key_exists('avatar_data_uri', $overrides)) {
            $avatarDataURI = $overrides['avatar_data_uri'];

            $rawDocument['avatar_data_uri'] = $avatarDataURI;
            $rawDocument['status']['base64_encoded_avatar'] = 'data:image/jpeg;base64,'.$avatarDataURI;
        }

        if (array_key_exists('extended_entities', $this->rawDocument)) {
            $mediaDataURI = $this->rawDocument['extended_entities']['media'][0]['media_url'];
            $rawDocument['_media_url'] = $mediaDataURI;

            if (array_key_exists('media_data_uri', $overrides)) {
                $mediaDataURI = $overrides['media_data_uri'];

                $rawDocument['media_data_uri'] = $mediaDataURI;
                $rawDocument['status']['base64_encoded_media'] = 'data:image/jpeg;base64,'.$mediaDataURI;
            }
        }

        return new self(
            $this->id,
            $this->hash,
            $this->username,
            $this->name,
            $this->text,
            $avatarDataURI,
            $this->statusId,
            json_encode($rawDocument, JSON_OBJECT_AS_ARRAY),
            $this->createdAt,
            $this->updatedAt,
            $this->isStarred,
            $this->isIndexed,
            $this->isPublished
        );
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
}