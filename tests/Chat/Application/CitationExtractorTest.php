<?php
declare(strict_types=1);

namespace App\Tests\Chat\Application;

use App\Chat\Application\CitationExtractor;
use App\Chat\Domain\Retrieval\RetrievedHit;
use PHPUnit\Framework\TestCase;

final class CitationExtractorTest extends TestCase
{
    private function hit(string $publicationId): RetrievedHit
    {
        return new RetrievedHit(
            publicationId: $publicationId,
            screenName: 'lemonde.fr',
            snapshotDate: '2025-03-04',
            url: 'https://bsky.app/profile/lemonde.fr/post/x',
            text: 'sample',
            reposts: 1,
            likes: 1,
            distance: 0.1,
        );
    }

    public function testExtractsSingleCitation(): void
    {
        $extractor = new CitationExtractor();
        $hits = [$this->hit('pub-1'), $this->hit('pub-2')];
        $result = $extractor->extract('La couverture est ample [1].', $hits);
        self::assertSame(['pub-1'], $result);
    }

    public function testExtractsCitationsInOrderOfFirstAppearance(): void
    {
        $extractor = new CitationExtractor();
        $hits = [$this->hit('pub-a'), $this->hit('pub-b'), $this->hit('pub-c')];
        $result = $extractor->extract('Voir [3] et [1]. Puis [3] à nouveau.', $hits);
        self::assertSame(['pub-c', 'pub-a'], $result);
    }

    public function testIgnoresOutOfRangeIndices(): void
    {
        $extractor = new CitationExtractor();
        $hits = [$this->hit('only-one')];
        $result = $extractor->extract('Voir [1] et [42].', $hits);
        self::assertSame(['only-one'], $result);
    }

    public function testReturnsEmptyWhenNoMarkers(): void
    {
        $extractor = new CitationExtractor();
        $hits = [$this->hit('p')];
        self::assertSame([], $extractor->extract('Pas de citations ici.', $hits));
    }
}
