<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Dto;

use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\Exception\InvalidTagPropertyException;
use DateTimeInterface;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function count;
use function get_class;
use function implode;
use function in_array;
use function lcfirst;

class TaggedTweet
{
    public static function fromLegacyProps(array $properties): self
    {
        $keys = [
            'hash',
            'text',
            'screen_name',
            'name',
            'user_avatar',
            'status_id',
            'api_document',
            'created_at',
            'identifier',
        ];

        foreach ($keys as $name) {
            if (!array_key_exists($name, $properties)) {
                throw new InvalidTagPropertyException(
                    sprintf('Missing "%s" property', $name)
                );
            }
        }

        return self::from(
            $properties['hash'],
            $properties['text'],
            $properties['screen_name'],
            $properties['name'],
            $properties['user_avatar'],
            $properties['status_id'],
            $properties['api_document'],
            $properties['created_at'],
            $properties['identifier']
        );
    }

    public static function from(
        string $hash,
        string $text,
        string $screenName,
        string $name,
        string $avatarUrl,
        string $documentId,
        string $document,
        DateTimeInterface $publishedAt,
        string $token
    ): self {
        return new self(
            $hash,
            $text,
            $screenName,
            $name,
            $avatarUrl,
            $documentId,
            $document,
            $publishedAt,
            $token
        );
    }

    private string $hash;

    private string $name;

    private string $screenName;

    private string $avatarUrl;

    private string $document;

    private string $documentId;

    private DateTimeInterface $publishedAt;

    private string $token;

    private string $text;

    private ?Tag $tag = null;

    private function __construct(
        string $hash,
        string $text,
        string $screenName,
        string $name,
        string $avatarUrl,
        string $documentId,
        string $document,
        DateTimeInterface $publishedAt,
        string $token
    ) {
        $this->hash        = $hash;
        $this->text        = $text;
        $this->screenName  = $screenName;
        $this->name        = $name;
        $this->avatarUrl   = $avatarUrl;
        $this->documentId  = $documentId;
        $this->document    = $document;
        $this->publishedAt = $publishedAt;
        $this->token       = $token;
    }

    public function avatarUrl(): string
    {
        return $this->avatarUrl;
    }

    public function document(): string
    {
        return $this->document;
    }

    public function documentId(): string
    {
        return $this->documentId;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function publishedAt(): DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }

    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
    }

    public function tag(): Tag
    {
        return $this->tag;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function toLegacyProps(): array
    {
        return [
            'hash'              => $this->hash,
            'text'              => $this->text,
            'screen_name'       => $this->screenName,
            'name'              => $this->name,
            'user_avatar'       => $this->avatarUrl,
            'status_id'         => $this->documentId,
            'api_document'      => $this->document,
            'original_document' => $this->document,
            'created_at'        => $this->publishedAt,
            'identifier'        => $this->token,
        ];
    }

    public function toStatus(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ?PublishersList $aggregate = null
    ): TweetInterface {
        $status = new Tweet();

        $properties = [
            'hash'         => $this->hash,
            'text'         => $this->text,
            'screen_name'  => $this->screenName,
            'name'         => $this->name,
            'user_avatar'  => $this->avatarUrl,
            'status_id'    => $this->documentId,
            'api_document' => $this->document,
            'created_at'   => $this->publishedAt,
            'identifier'   => $this->token,
        ];

        if (!array_key_exists('created_at', $properties)) {
            $properties['created_at'] = new \DateTime();
        }
        if (!array_key_exists('updated_at', $properties)) {
            $properties['updated_at'] = null;
        }

        $entity           = get_class($status);
        $fieldNames       = $entityManager->getClassMetadata($entity)->getFieldNames();
        $missedProperties = [];

        $inflectorFactory = InflectorFactory::create();
        $inflector = $inflectorFactory->build();

        foreach ($properties as $name => $value) {
            $classifiedName = $inflector->classify($name);

            if (in_array(lcfirst($classifiedName), $fieldNames, true)) {
                $method = 'set' . $classifiedName;
                $status->$method($value);
            } else {
                $missedProperties[] = $name . ': ' . $value;
            }
        }

        if (count($missedProperties) > 0) {
            $output = 'property missed at introspection for entity ' . $entity . "\n" .
                implode("\n", $missedProperties) . "\n";
            $logger->info($output);
        }

        // non-nullable and not available from the API
        $status->setIndexed(true);
        $status->setIdentifier($this->token);

        if ($aggregate !== null) {
            $status->addToAggregates($aggregate);
        }

        return $status;
    }

    public function token(): string
    {
        return $this->token;
    }
}
