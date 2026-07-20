# Event Voting & Summary — `eat` App Core Concept

**Date:** 2026-07-21
**Project:** `eat`
**Status:** Approved
**Builds on:** [Events domain](2026-07-20-events-domain-design.md) · nationality catalogue + attribute enums (merged)

## Goal

The heart of `eat`: for each event, every attendee votes for the **nationalities**
and **criteria** (price / diet / style) they'd like. When the creator validates
(closes voting), a **summary** is generated and made available to all attendees.

## Event lifecycle

An event gains a status:

- **`voting`** (default, on creation) — attendees may vote and change their vote.
- **`closed`** — the creator has validated; votes are frozen; the summary is shown.

The creator may **force-close at any time** (even if not everyone has voted).
Attendees who never voted are simply absent from the tally.

## Data model

A **ballot** per (attendee, event), with its selections in linked tables.

### `events` (added columns)

| column         | type                    | notes                          |
|----------------|-------------------------|--------------------------------|
| `status`       | string (enum-backed)    | `voting` / `closed`, default `voting` |
| `validated_at` | timestamp, nullable     | set when the creator closes    |

Backed by an `App\Enums\EventStatus` enum (`Voting`, `Closed`), cast on the model.

### `event_votes` (the ballot)

| column         | type              | notes                              |
|----------------|-------------------|------------------------------------|
| `id`           | bigint PK         |                                    |
| `event_id`     | FK → events       | `onDelete cascade`                 |
| `user_id`      | FK → users        | `onDelete cascade`                 |
| `submitted_at` | timestamp         |                                    |
| timestamps     |                   |                                    |
| unique         | `(event_id, user_id)` | one ballot per attendee per event |

The existence of an `event_votes` row means "this attendee has voted".

### `event_vote_nationality`

- `event_vote_id` FK (cascade), `nationality_id` FK (cascade), `unique(event_vote_id, nationality_id)`

### `event_vote_attribute`

- `event_vote_id` FK (cascade), `type` string (`price`/`diet`/`style`), `value` string
- `unique(event_vote_id, type, value)`

`type`/`value` are validated against the merged attribute system
(`AttributeType::from($type)->values()`).

## Models & relationships

- **Event** (extended): `status` cast to `EventStatus`, `validated_at` cast to
  datetime; `votes()` hasMany `EventVote`; helpers `isVoting()` / `isClosed()`.
- **EventVote** (new): belongsTo `Event`, belongsTo `User`;
  `nationalities()` belongsToMany `Nationality` (via `event_vote_nationality`);
  `attributes()` hasMany `EventVoteAttribute` (rows of type/value).
- **EventVoteAttribute** (new): belongsTo `EventVote`; casts `type` to `AttributeType`.

## Voting (mechanics + UI)

- **`GET /events/{event}/vote`** → `Events/Vote.vue`, for **attendees** while
  status is `voting`. Pre-filled with the attendee's current ballot if any.
  - Nationalities: multi-select checkboxes (alphabetical, searchable).
  - Criteria: multi-select checkboxes per type (Price, Diet, Style), sourced from
    `AttributeType::cases()` → `options()`.
- **`POST /events/{event}/vote`** → upsert the ballot (create or replace the
  attendee's selections). Editable until the event is closed.

Validation of the submission:
- `nationalities.*` must exist in `nationalities`.
- criteria payload shape `{ price: [...], diet: [...], style: [...] }`; each value
  must belong to its type's allowed values.

## Validation (closing)

- **`POST /events/{event}/validate`** → creator only, while `voting`. Sets
  `status = closed`, `validated_at = now()`. Votes are frozen thereafter.
- The Show page shows the creator a participation count ("3/5 voted") and an active
  **Validate** button at any time.

## Summary

Computed server-side from the ballots **when the event is `closed`** (deterministic;
no stored snapshot, since votes are frozen):

- **Nationalities** — ranked by vote count, winner first; ties share rank.
- **Per criterion type** — count per value, most popular highlighted.
- **Participation** — "5/7 voted".

Rendered on the Show page (read-only) for all attendees once closed.

## Secret ballot

Tallies are **hidden until the event is closed**. Before closing, the Show page
exposes only the participation count, never the running results — so early votes
can't sway later ones.

## Authorization (extend `EventPolicy`)

- `vote` — attendee **and** status `voting`
- `validate` — creator **and** status `voting`
- summary follows `view` (attendees only), shown only when `closed`

## Testing (TDD)

- ballot is unique per (event, user); re-voting updates rather than duplicates
- an attendee can submit nationalities + criteria; rows land in the child tables
- a non-attendee cannot vote (403)
- voting is rejected once the event is closed
- the creator can validate → status `closed`, `validated_at` set; non-creator 403
- validating freezes voting (later vote attempts rejected)
- summary aggregates correctly (nationality winner by count; per-type tallies)
- results are hidden before closing (Show props omit tallies while `voting`)
- invalid criteria value (wrong value for a type) is rejected

Model-level: `EventVote`/`EventVoteAttribute` relationships and casts; factories.

## Out of scope (future)

Ranked/weighted voting, live results, per-attendee notifications, re-opening a
closed event, tie-break rules beyond "shared rank", exporting the summary. Diet is
tallied like the others (not enforced as a hard constraint) for now.
