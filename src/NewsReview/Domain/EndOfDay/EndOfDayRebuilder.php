<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

/**
 * Rebuilds a day's top 10 as it stood at each cut-off, from the times of each
 * post's reposts and likes.
 *
 * A count at a cut-off can't exceed today's, so accounts are visited by their
 * best current repost count and the search stops once none of the remaining
 * ones can enter the top 10; within an account, posts are visited the same way
 * and the search stops once the next can't beat the account's best. Only the
 * posts actually visited have their timelines read.
 */
final class EndOfDayRebuilder
{
    private const TOP = 10;

    public function __construct(private readonly Interactions $interactions)
    {
    }

    /** @param list<Candidate> $candidates */
    public function rebuild(ParisDay $day, array $candidates): Rebuild
    {
        $byUri = [];
        foreach ($candidates as $candidate) {
            $byUri[$candidate->uri] = $candidate;
        }
        $now = $this->interactions->currentCounts(array_keys($byUri));

        $postsByAccount = [];
        foreach ($now as $uri => $counts) {
            $postsByAccount[$byUri[$uri]->handle][] = $uri;
        }
        foreach ($postsByAccount as &$uris) {
            usort($uris, static fn(string $a, string $b): int => $now[$b]['reposts'] <=> $now[$a]['reposts']);
        }
        unset($uris);
        uksort(
            $postsByAccount,
            static fn(string $a, string $b): int => $now[$postsByAccount[$b][0]]['reposts'] <=> $now[$postsByAccount[$a][0]]['reposts'],
        );

        $timelines = [];
        $topTens = [];
        $measured = [];
        foreach ($day->cutOffs() as $label => $cutOff) {
            /** @var array<string, RankedPost> $best */
            $best = [];
            foreach ($postsByAccount as $handle => $uris) {
                if (count($best) >= self::TOP && $now[$uris[0]]['reposts'] < $this->tenth($best)) {
                    break;
                }
                foreach ($uris as $uri) {
                    if (isset($best[$handle]) && $now[$uri]['reposts'] <= $best[$handle]->reposts) {
                        break;
                    }
                    $timelines[$uri] ??= [
                        $this->interactions->repostTimes($uri),
                        $this->interactions->likeTimes($uri),
                    ];
                    $post = new RankedPost(
                        (string) $handle,
                        $uri,
                        self::countBefore($timelines[$uri][0], $cutOff),
                        self::countBefore($timelines[$uri][1], $cutOff),
                    );
                    $measured[$uri][$label] = new RebuiltMeasurement($byUri[$uri], $label, $cutOff, $post->reposts, $post->likes);
                    if (!isset($best[$handle]) || self::ranksAbove($post, $best[$handle])) {
                        $best[$handle] = $post;
                    }
                }
            }
            $ranked = array_values($best);
            usort($ranked, static fn(RankedPost $a, RankedPost $b): int => self::ranksAbove($a, $b) ? -1 : (self::ranksAbove($b, $a) ? 1 : 0));
            $topTens[$label] = array_slice($ranked, 0, self::TOP);
        }

        $measurements = [];
        foreach ($measured as $uri => $byCutOff) {
            // Every visited post is stored at both cut-offs.
            foreach ($day->cutOffs() as $label => $cutOff) {
                $timelines[$uri] ??= [$this->interactions->repostTimes($uri), $this->interactions->likeTimes($uri)];
                $measurements[] = $byCutOff[$label] ?? new RebuiltMeasurement(
                    $byUri[$uri],
                    $label,
                    $cutOff,
                    self::countBefore($timelines[$uri][0], $cutOff),
                    self::countBefore($timelines[$uri][1], $cutOff),
                );
            }
        }

        return new Rebuild($day, $topTens, $measurements);
    }

    /** @param array<string, RankedPost> $best */
    private function tenth(array $best): int
    {
        $reposts = array_map(static fn(RankedPost $p): int => $p->reposts, $best);
        rsort($reposts);

        return $reposts[self::TOP - 1];
    }

    /** @param list<?\DateTimeImmutable> $times */
    private static function countBefore(array $times, \DateTimeImmutable $cutOff): int
    {
        return count(array_filter($times, static fn(?\DateTimeImmutable $t): bool => $t !== null && $t < $cutOff));
    }

    private static function ranksAbove(RankedPost $a, RankedPost $b): bool
    {
        return [$a->reposts, $a->likes] > [$b->reposts, $b->likes];
    }
}
