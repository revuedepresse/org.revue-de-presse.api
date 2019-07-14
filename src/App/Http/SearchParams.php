<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\Request;

class SearchParams
{
    /**
     * @var PaginationParams
     */
    private $paginationParams;

    /**
     * @var string|null
     */
    private $keyword;

    /**
     * @var array
     */
    private $params;

    /**
     * @param PaginationParams $paginationParams
     * @param string|null      $keyword
     * @param array            $filteredParams
     */
    public function __construct(
        PaginationParams $paginationParams,
        string $keyword = null,
        array $filteredParams = []
    ) {
        $this->paginationParams = $paginationParams;
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
        $paginationParams = PaginationParams::fromRequest($request);
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
                    $filteredParams[$name] = intval($value);
                }

                if ($params[$name] == 'string' && !empty($value)) {
                    $filteredParams[$name] = trim((string) $value);
                }

                if ($params[$name] == 'boolean' || $params[$name] == 'bool') {
                    $filteredParams[$name] = boolval(intval($value));
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
            $paginationParams,
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
            'page_index' => $this->paginationParams->pageIndex,
            'page_size' => $this->paginationParams->pageSize,
            'keyword' => $this->keyword,
        ];
    }

    /**
     * @return int
     */
    public function getPageIndex(): int
    {
        return $this->paginationParams->pageIndex;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->paginationParams->pageSize;
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
        return $this->paginationParams->getFirstItemIndex();
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
