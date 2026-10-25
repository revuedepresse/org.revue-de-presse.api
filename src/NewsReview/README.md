# NewsReview — daily top 10

`GET /api/highlights?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD` serves a day's top
10 from a JSON snapshot in `src/Bluesky/Resources/`.

## Lists

`?list=` picks the Bluesky list whose members are ranked:

| `list` | Members |
|---|---|
| `fr` (default) | French media |
| `coop-medias` | the `coop-medias.org` list |
| `fr-coop-medias` | members of either list, each post counted once |

## Rankings

`?ranking=` picks how the day's posts are ranked:

| `ranking` | Counts a post is ranked on |
|---|---|
| `first-sighting` | the reposts and likes measured when the post was first fetched |
| `end-of-day` | the reposts and likes as they stood at the end of the publication day |

## Snapshot files

Every list and ranking has its own file per day, all in `src/Bluesky/Resources/`:

| | `first-sighting` | `end-of-day` |
|---|---|---|
| `fr` | `<date>.json` | `<date>.fr.end-of-day.json` |
| `coop-medias` | `<date>.coop-medias.first-sighting.json` | `<date>.coop-medias.end-of-day.json` |
| `fr-coop-medias` | `<date>.fr-coop-medias.first-sighting.json` | `<date>.fr-coop-medias.end-of-day.json` |

French media first-sighting snapshots predate the other lists and rankings and
keep their `<date>.json` name; every other file is `<date>.<list>.<ranking>.json`
(see `PublisherList::snapshotFileName()`).

Without `?ranking=`, `HIGHLIGHTS_DEFAULT_RANKING` decides (`end-of-day` in
`.env`). It switches every client (site, app, newsletter readers of the API)
at once, and setting it to `first-sighting` reverts.

A defaulted `end-of-day` falls back to `first-sighting` on days that have no
end-of-day snapshot: French media end-of-day snapshots start on 2025-03-04, and
the days before only have first-sighting ones. A ranking asked for by name is
never swapped for another one, and the two never share a cache key.

Unknown lists or rankings answer 400.

## Measurements

Each row of `status_popularity` is one measurement of a post's counts.
`measured_by` tells where it comes from:

- `fetch`: read when the worker fetched the post (every row before this column
  existed);
- `rebuild`: counts at a cut-off, rebuilt afterwards from the times of the
  post's reposts and likes. `checked_at` is the cut-off, in UTC.

The cut-offs of a calendar day in Europe/Paris are 22:00, when the nightly
ranking runs, and midnight, when the day ends.

## Rebuilding a day

```sh
bin/console highlights:rebuild-end-of-day 2026-10-23 --list=fr --dry-run
bin/console highlights:rebuild-end-of-day 2026-10-23 --list=coop-medias
```

For each post published that Paris day by a member of the list:

1. current counts come from Bluesky's `app.bsky.feed.getPosts`;
2. like times come from `app.bsky.feed.getLikes`;
3. repost times come from [Constellation](https://constellation.microcosm.blue),
   a public index of links between AT Protocol records: each repost's record
   key is a timestamp identifier (TID) that encodes when it was created.

A count at a cut-off can't exceed today's, so accounts are visited by their
best current repost count, and the rebuild stops once no remaining account can
enter the top 10. Only those posts' timelines are read.

`--dry-run` prints the top 10 at each cut-off and stores nothing. Otherwise
the counts of every visited post are stored at both cut-offs as
`measured_by = 'rebuild'`; re-running a day replaces them, and fetched rows are
never touched.

Likes or reposts removed since the day are missing from a rebuild.
