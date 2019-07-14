<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\Request;

class PaginationParams
{
    /**
     * @var int
     */
    public $pageIndex;

    /**
     * @var int
     */
    public $pageSize;

    /**
     * @param int $pageIndex
     * @param int $pageSize
     */
    public function __construct(int $pageIndex, int $pageSize)
    {
        if ($this->pageSize < 0) {
            throw new \LogicException('A page size should be greater than 0');
        }

        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
    }

    /**
     * @param Request $request
     * @return PaginationParams
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            intval($request->get('pageIndex', 1)),
            intval($request->get('pageSize', 25))
        );
    }

    /**
     * @return int
     */
    public function getFirstItemIndex(): int
    {
        return ($this->pageIndex - 1) * $this->pageSize;
    }
}
