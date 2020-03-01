<?php
declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

class PaginationParams
{
    public int $pageIndex;

    public int $pageSize;

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
     *
     * @return \App\Http\PaginationParams
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            (int) $request->get('pageIndex', 1),
            (int) $request->get('pageSize', 25)
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