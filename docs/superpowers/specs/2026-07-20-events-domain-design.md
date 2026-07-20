# Events Domain — `eat` App First Feature

**Date:** 2026-07-20
**Project:** `eat`
**Status:** Approved
**Builds on:** [Laravel+Vue auth foundation](2026-07-20-laravel-vue-auth-profiles-design.md)

## Goal

The first real feature of `eat`: **events**. A user creates an event (a shared
meal), gets a shareable invite code, and other users join via that code. Every
event has a creator (owner) and a list of attendees. Delivered as a full vertical
slice — data layer, controllers, routes, Vue UI, authorization, and tests.

## Data model

### `events` table

| column        | type                 | notes                                   |
|---------------|----------------------|-----------------------------------------|
| `id`          | bigint PK            |                                         |
| `creator_id`  | FK → `users.id`      | owner; `onDelete cascade`               |
| `name`        | string               |                                         |
| `date`        | date                 |                                         |
| `meal`        | string (enum-backed) | `breakfast` / `lunch` / `dinner`        |
| `invite_code` | string, unique, indexed | auto-generated on create             |
| timestamps    |                      |                                         |

Column is named `meal` (semantically a meal). The concrete clock-time is
intentionally **not** here — it becomes a future votable table.

### `event_user` pivot (attendees, many-to-many)

- `event_id` FK → events (`onDelete cascade`)
- `user_id` FK → users (`onDelete cascade`)
- `timestamps`
- `unique(event_id, user_id)` — a user joins an event at most once

## Enum

`app/Enums/Meal.php` — native backed string enum:

```php
enum Meal: string {
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';

    public function label(): string; // 'Breakfast' | 'Lunch' | 'Dinner'
}
```

Cast on the Event model so `$event->meal` is a typed `Meal`.

## Models & relationships

**Event**
- `#[Fillable([...])]` attribute style (matches existing User model)
- `creator()` — belongsTo User (`creator_id`)
- `attendees()` — belongsToMany User (via `event_user`), `withTimestamps()`
- casts: `date` → `date`, `meal` → `Meal::class`
- `creating` model hook generates a unique 8-char uppercase alphanumeric
  `invite_code` (regenerates on collision)
- route key: default `id`

**User** (add relationships)
- `createdEvents()` — hasMany Event (`creator_id`)
- `events()` — belongsToMany Event (events attended)

**Creator auto-join:** the store controller attaches the creator to `attendees`
immediately after creating the event.

## Routes & UI

Inertia + Vue 3, wrapped in Breeze's `AuthenticatedLayout`. All routes behind
`auth` middleware.

| Method + URI                     | Action / Page                                        |
|----------------------------------|------------------------------------------------------|
| `GET /events`                    | **Index** — my events (created + joined)             |
| `GET /events/create`             | **Create** form (name, date, meal picker)            |
| `POST /events`                   | store → generate code, creator auto-joins, redirect  |
| `GET /events/{event}`            | **Show** — details, attendees, shareable invite code |
| `GET /events/join/{code?}`       | **Join** — code prefilled from shared link or typed  |
| `POST /events/join`              | join by code (validate, block double-join)           |
| `DELETE /events/{event}`         | delete (creator only)                                |
| `DELETE /events/{event}/leave`   | leave an event you joined (non-creator)              |

**Vue pages** under `resources/js/Pages/Events/`: `Index.vue`, `Create.vue`,
`Show.vue`, `Join.vue`.

## Authorization — `EventPolicy`

- **view**: only attendees (incl. creator) may see event details → else 403
- **delete**: creator only
- **join**: valid code required; already-a-member is a friendly no-op (not an error);
  double-join prevented by the pivot unique constraint + a guard
- **leave**: attendees who are not the creator

## Testing (feature tests)

- store: persists event, generates a unique code, auto-joins the creator
- index: shows only events I created or joined (not others')
- join: valid code adds me to attendees; invalid code → validation error;
  joining twice is a no-op / blocked
- show: non-attendee receives 403; attendee sees the page
- delete: creator can delete; non-creator gets 403
- leave: attendee is removed; creator cannot "leave" their own event

Model-level: `EventFactory` produces valid events; invite codes are unique.

## Factories & seeders

- `EventFactory` — random name/date/meal, a `creator_id`
- `DatabaseSeeder` — give `test@example.com` a couple of owned events, each with
  a few random attendees, so the app has real data on first load

## Out of scope (future)

Hour/time voting (separate table), event editing, notifications, RSVP states
(maybe/declined), comments. This slice is create / list / show / join / leave /
delete.
