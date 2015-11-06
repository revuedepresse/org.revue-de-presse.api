<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity;

use WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export\ExportableInterface,
    WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Import\ImportableInterface,
    WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints as WeavingTheWebAssert;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="weaving_perspective")
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository")
 * @WeavingTheWebAssert\Perspective(groups="public_perspectives")
 */
class Perspective implements ExportableInterface, ImportableInterface
{
    const STATUS_DISABLED           = 0;

    const STATUS_DEFAULT            = 1;

    const STATUS_PUBLIC             = 2;

    const STATUS_EXPORTABLE         = 3;

    const STATUS_HAS_INVALID_VALUE  = 4;

    const STATUS_IMPORTABLE         = 5;

    /**
     * Default perspective
     */
    const TYPE_DEFAULT      = 0;

    /**
     * Query perspective
     */
    const TYPE_QUERY        = 1;

    /**
     * JSON perspective
     */
    const TYPE_JSON         = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="per_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="per_status", type="integer", options={"default": 1})
     */
    protected $status = self::STATUS_DEFAULT;

    /**
     * @var integer
     *
     * @ORM\Column(name="per_type", type="integer", options={"default": 0}))
     */
    protected $type = self::TYPE_DEFAULT;

    /**
     * @var string
     *
     * @ORM\Column(name="per_name", type="string", nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="per_uuid", type="string", length=36, nullable=true)
     */
    protected $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="per_hash", type="string", length=40, nullable=true)
     */
    protected $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="per_description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="per_value", type="text", nullable=true)
     */
    protected $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="per_date_creation", type="datetime", nullable=true)
     */
    protected $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="per_date_update", type="datetime", nullable=true)
     */
    protected $updateDate;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set status
     *
     * @param  integer     $status
     * @return Perspective
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set type
     *
     * @param  integer     $type
     * @return Perspective
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set value
     *
     * @param  string      $value
     * @return Perspective
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set creationDate
     *
     * @param  \DateTime   $creationDate
     * @return Perspective
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set updateDate
     *
     * @param  \DateTime   $updateDate
     * @return Perspective
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set description
     *
     * @param  string      $description
     * @return Perspective
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return bool
     */
    public function isExportable()
    {
        return $this->status === self::STATUS_EXPORTABLE;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="per_date_export", type="datetime", nullable=true)
     */
    protected $exportedAt;

    public function getExportedAt()
    {
        return $this->exportedAt;
    }

    public function setExportedAt(\DateTime $exportedAt)
    {
        $this->exportedAt = $exportedAt;

        return $this;
    }
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="per_date_import", type="datetime", nullable=true)
     */
    protected $importedAt;

    public function getImportedAt()
    {
        return $this->importedAt;
    }

    public function setImportedAt(\DateTime $importedAt)
    {
        $this->importedAt = $importedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isImportable()
    {
        return $this->status === self::STATUS_IMPORTABLE;
    }

    /**
     * @return int
     */
    public function isQueryPerspective()
    {
        return $this->type = self::TYPE_QUERY;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->status === self::STATUS_PUBLIC;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return !$this->isPublic();
    }

    /**
     * @return int
     */
    public function isJsonPerspective()
    {
        return $this->type = self::TYPE_JSON;
    }

    /**
     * @return $this
     */
    public function markAsHavingInvalidValue()
    {
        $this->status = self::STATUS_HAS_INVALID_VALUE;

        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getJsonFilename()
    {
        if ($this->type === self::TYPE_JSON && $this->status === self::STATUS_IMPORTABLE) {
            $fileObject = new \SplFileObject($this->getValue());

            return $fileObject->getFilename();
        } else {
            throw new \Exception('No JSON filename available for this perspective');
        }
    }

    /**
     * @param $sourceDirectory
     * @return string
     */
    public function getNewFilename($sourceDirectory)
    {
        $filename = $this->getJsonFilename();

        return realpath($sourceDirectory) . '/_' . $filename;
    }
}
