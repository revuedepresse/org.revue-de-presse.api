<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\StatusInterface;
use Ramsey\Uuid\UuidInterface;

class NotFoundStatus
{
    /**
     * @var string
     */
    private UuidInterface $id;

    /**
     * @var Status
     */
    private ?Status $status = null;

    /**
     * @var ArchivedStatus
     */
    private ?ArchivedStatus $archivedStatus = null;

    /**
     * @param $status
     * @param $archivedStatus
     */
    public function __construct(Status $status = null, ArchivedStatus $archivedStatus = null)
    {
        $this->archivedStatus = $archivedStatus;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return StatusInterface
     */
    public function getStatus()
    {
        if ($this->status === null) {
            return $this->archivedStatus;
        }

        return $this->status;
    }
}
