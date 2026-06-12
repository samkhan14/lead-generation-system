# Phase 1 — Core Database (CRM + Lead Intelligence)

## Status

**Approved and implemented.**

## Approved Decisions

| Decision | Choice |
|----------|--------|
| Score storage | Separate `leads` + `lead_scores` tables |
| Roles | `super_admin`, `agent` — super admin bypasses all permissions via `Gate::before` |
| Status field | Nullable on `leads` — reserved for future scraping workflow |
| UI | Minimal Inertia Vue 3 UI (list, create, show, delete) |
| Auth | Laravel Breeze (Inertia + Vue) |
| Soft deletes | Enabled on `leads` |

## Goal

Establish the lean CRM + Lead Intelligence foundation: base app structure, configuration, and **only** the core database tables required for users, roles/permissions, leads, and lead scores.

Keep the schema small. Do not build deals, clients, or projects yet.

## Implemented Stack

- Laravel 13 + Breeze (Inertia + Vue 3)
- Spatie Laravel Permission
- Tables: `users`, Spatie permission tables, `leads`, `lead_scores`
- Soft deletes on `leads`
- Super admin permission bypass in `AppServiceProvider`

## Seed Users

| Email | Password | Role |
|-------|----------|------|
| `superadmin@example.com` | `password` | Super Admin |
| `agent@example.com` | `password` | Agent |

## Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/leads` | `leads.index` |
| GET | `/leads/create` | `leads.create` |
| POST | `/leads` | `leads.store` |
| GET | `/leads/{lead}` | `leads.show` |
| DELETE | `/leads/{lead}` | `leads.destroy` |

All lead routes require `auth` + `verified`.

## Authorization

Permissions are stored in the database via Spatie and assigned to roles. Roles are assigned to users.

- Roles: `super_admin`, `agent` (seeded in `RolesAndPermissionsSeeder`)
- Agent receives: `leads.view`, `leads.create`, `leads.update`, `leads.delete`
- Super admin bypasses all permission checks via `Gate::before`
- Route protection uses Spatie `permission` middleware on `LeadController`
- No policies or permission enums

## Schema Summary

### `leads`

Core scraped/captured lead record. `status` is nullable for future use.

### `lead_scores`

Score history per lead: `score`, `score_grade`, `factors` (json), `calculated_at`.

Latest score accessed via `Lead::latestScore()` relationship.

## Out of Scope (still blocked)

- Deals module
- Clients module
- Projects module
- Lead status workflow / scraping pipeline UI

## Exit Criteria

- [x] Inertia + Vue base layout renders
- [x] Spatie roles/permissions migrated and seeded
- [x] `leads` and `lead_scores` tables migrated
- [x] Models, factories, policies, seeders, and tests exist
- [x] Minimal leads UI (index, create, show, delete)
- [x] No deals, clients, or projects code

## Next Phase Preview

- Scraping ingestion pipeline
- Score calculation job (queued)
- Lead assignment workflow
- Status enum and filtering (when scraping workflow is defined)
