<?php

namespace App\Aggregate\Controller;

use Symfony\Component\HttpFoundation\Request;

class SearchParams
{
    private $pageIndex;
    private $pageSize;
    private $keyword;
    private $params;

    /**
     * @param int         $pageIndex
     * @param int         $pageSize
     * @param string|null $keyword
     * @param array       $filteredParams
     */
    public function __construct(
        int $pageIndex,
        int $pageSize,
        string $keyword = null,
        array $filteredParams = []
    ) {
        if ($this->pageSize < 0) {
            throw new \LogicException('A page size should be greater than 0');
        }
        $this->pageSize = $pageSize;

        $this->pageIndex = $pageIndex;
        $this->keyword = $keyword;
        $this->params = $filteredParams;
    }

    /**
     * @param Request $request
     * @param array   $params
     * @return SearchParams
     */
    public static function fromRequest(Request $request, array $params = []): self
    {
        $pageIndex = intval($request->get('pageIndex', 1));
        $pageSize = intval($request->get('pageSize', 25));
        $keyword = $request->get('keyword', null);

        $filteredParams = [];
        $paramsNames = array_keys($params);
        array_walk(
            $paramsNames,
            function ($name) use ($request, $params, &$filteredParams) {
                $value = $request->get($name, null);
                $filteredParams[$name] = $value;

                if ($params[$name] == 'int') {
                    $filteredParams[$name] = intval($value);
                }
            }
        );

        return new self($pageIndex, $pageSize, $keyword, $filteredParams);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'page_index' => $this->pageIndex,
            'page_size' => $this->pageSize,
            'keyword' => $this->keyword,
        ];
    }

    /**
     * @return int
     */
    public function getPageIndex(): int
    {
        return $this->pageIndex;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return null|string
     */
    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    /**
     * @return bool
     */
    public function hasKeyword(): bool
    {
        return $this->keyword !== null;
    }

    /**
     * @return int
     */
    public function getFirstItemIndex(): int
    {
        return ($this->pageIndex - 1) * $this->pageSize;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
