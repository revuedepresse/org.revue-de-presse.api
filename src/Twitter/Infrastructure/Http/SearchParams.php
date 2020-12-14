<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http;

use App\NewsReview\Domain\Repository\SearchParamsInterface;
use Symfony\Component\HttpFoundation\Request;
use function array_key_exists;
use function array_keys;
use function array_values;
use function array_walk;
use function boolval;
use function in_array;
use function intval;
use function is_array;
use function serialize;
use function sha1;
use function trim;

class SearchParams implements SearchParamsInterface
{
    public const PARAM_AGGREGATE_IDS = 'aggregateIds';

    /**
     * @var PaginationParams
     */
    private PaginationParams $paginationParams;

    /**
     * @var string|null
     */
    private ?string $keyword;

    /**
     * @var array
     */
    private array $params;

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
     *
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

                if ($value === null) {
                    return;
                }

                $filteredParams[$name] = $value;

                if ($params[$name] === 'int' || $params[$name] === 'integer') {
                    $filteredParams[$name] = intval($value);
                }

                if ($params[$name] === 'string' && !empty($value)) {
                    $filteredParams[$name] = trim((string) $value);
                }

                if ($params[$name] === 'boolean' || $params[$name] === 'bool') {
                    $filteredParams[$name] = boolval(intval($value));
                }

                if ($params[$name] === 'datetime') {
                    $filteredParams[$name] = new \DateTime($value, new \DateTimeZone('Europe/Paris'));
                }

                if ($params[$name] === 'array') {
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
     * @param string $name
     * @param array  $options
     *
     * @return bool
     */
    public function paramBelongsTo(string $name, array $options): bool
    {
        return $this->hasParam($name) &&
            in_array($this->params[$name], array_values($options), true);
    }

    /**
     * @return string
     */
    public function getFingerprint()
    {
        return sha1(serialize($this));
    }
}