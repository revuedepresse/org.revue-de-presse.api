<?php
declare (strict_types=1);

namespace App\NewsReview\Domain\Entity;

use App\Twitter\Domain\Curation\Entity\Highlight;

class HighlightsCollection
{
    private array $highlights;

    public function __construct(array $highlights) {
        $compliantHighlights = array_map(
            static function ($highlight) {
                if (!($highlight instanceof Highlight)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Highlight should be instance of "%s"',
                            Highlight::class
                        )
                    );
                }

                return $highlight;
            },
            $highlights,
        );

        $this->highlights = $compliantHighlights;
    }
}