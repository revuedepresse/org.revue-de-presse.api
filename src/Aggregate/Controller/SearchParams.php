<?php

namespace App\Aggregate\Controller;

use Symfony\Component\HttpFoundation\Request;

class SearchParams
{
    private int $pageIndex;
    private int $pageSize;
    private string $keyword;
    private array $params;

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
        $pageIndex = (int) $request->get('pageIndex', 1);
        $pageSize = (int) $request->get('pageSize', 25);
        $keyword = $request->get('keyword', null);

        $filteredParams = [];
        $paramsNames = array_keys($params);
        array_walk(
            $paramsNames,
            function ($name) use ($request, $params, &$filteredParams) {
                $value = $request->get($name, null);

                if (is_null($value)) {
                    return;
                }

                $filteredParams[$name] = $value;

                if ($params[$name] == 'int' || $params[$name] == 'integer') {
                    $filteredParams[$name] = (int) $value;
                }

                if ($params[$name] == 'string' && !empty($value)) {
                    $filteredParams[$name] = trim((string) $value);
                }

                if ($params[$name] == 'boolean' || $params[$name] == 'bool') {
                    $filteredParams[$name] = boolval((int) $value);
                }

                if ($params[$name] == 'datetime') {
                    $filteredParams[$name] = new \DateTime($value, new \DateTimeZone('Europe/Paris'));
                }


                if ($params[$name] == 'array') {
                    $filteredParams[$name] = $value;

                    if (!is_array($value)) {
                        $filteredParams[$name] = [];
                    }
                }
            }
        );

        return new self(
            $pageIndex,
            $pageSize,
            $keyword,
            $filteredParams
        );
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

    /**
     * @param $name
     * @return bool
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    /**
     * @param string $name
     * @param        $value
     * @return bool
     */
    public function paramIs(string $name, $value): bool
    {
        return $this->hasParam($name) && $this->params[$name] === $value;
    }

    /**
     * @return string
     */
    public function getFingerprint()
    {
        return sha1(serialize($this));
    }
}
