<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

class PaginationParams
{
    public int $pageIndex = 1;

    public int $pageSize = 0;

    /**
     * @param int $pageIndex
     * @param int $pageSize
     */
    public function __construct(int $pageIndex, int $pageSize)
    {
        if ($pageSize < 0) {
            throw new \LogicException('A page size should be greater than 0');
        }

        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
    }

    /**
     * @param Request $request
     *
     * @return PaginationParams
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