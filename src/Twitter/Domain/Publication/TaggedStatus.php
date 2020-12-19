<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication;

use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\Exception\InvalidTagPropertyException;
use DateTimeInterface;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function count;
use function get_class;
use function implode;
use function in_array;
use function lcfirst;

class TaggedStatus
{
    /**
     * @param array $properties
     *
     * @return TaggedStatus
     */
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

    /**
     * @param string            $hash
     * @param string            $text
     * @param string            $screenName
     * @param string            $name
     * @param string            $avatarUrl
     * @param string            $documentId
     * @param string            $document
     * @param DateTimeInterface $publishedAt
     * @param string            $token
     *
     * @return TaggedStatus
     */
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
    ) {
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

    /**
     * @var string
     */
    private string $hash;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $screenName;

    /**
     * @var string
     */
    private string $avatarUrl;

    /**
     * @var string
     */
    private string $document;

    /**
     * @var string
     */
    private string $documentId;

    /**
     * @var DateTimeInterface
     */
    private DateTimeInterface $publishedAt;

    /**
     * @var string
     */
    private string $token;

    /**
     * @var string
     */
    private string $text;

    /**
     * @var Tag|null
     */
    private ?Tag $tag = null;

    /**
     * @param string            $hash
     * @param string            $text
     * @param string            $screenName
     * @param string            $name
     * @param string            $avatarUrl
     * @param string            $documentId
     * @param string            $document
     * @param DateTimeInterface $publishedAt
     * @param string            $token
     */
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

    /**
     * @return string
     */
    public function avatarUrl(): string
    {
        return $this->avatarUrl;
    }

    /**
     * @return string
     */
    public function document(): string
    {
        return $this->document;
    }

    /**
     * @return string
     */
    public function documentId(): string
    {
        return $this->documentId;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return DateTimeInterface
     */
    public function publishedAt(): DateTimeInterface
    {
        return $this->publishedAt;
    }

    /**
     * @return string
     */
    public function screenName(): string
    {
        return $this->screenName;
    }

    /**
     * @param Tag $tag
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return Tag
     */
    public function tag(): Tag
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function text(): string
    {
        return $this->text;
    }

    /**
     * @return array
     */
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

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     *
     * @param Aggregate|null         $aggregate
     *
     * @return StatusInterface
     * @throws Exception
     */
    public function toStatus(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ?Aggregate $aggregate = null
    ): StatusInterface {
        $status = new Status();

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

    /**
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }
}