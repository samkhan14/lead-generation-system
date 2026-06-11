# Data Model

## Current Database Baseline

The current schema is the Laravel default baseline:

- `users`
- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

No project-specific domain tables exist yet.

## Data Modeling Principles

- Model the business domain explicitly once the approved Phase 1 scope is known.
- Prefer normalized relational data for transactional workflows.
- Use clear foreign keys and indexes for ownership, lookup, and reporting paths.
- Use Laravel migrations as the source of truth for schema changes.
- Avoid nullable columns unless the missing state is meaningful and documented.
- Avoid JSON columns for core relational data unless the structure is genuinely flexible or external.
- Store derived values only when there is a documented performance or audit reason.
- Define deletion behavior before adding relationships.

## Naming Standards

- Tables use plural snake_case names.
- Columns use snake_case names.
- Foreign keys use Laravel conventions, such as `user_id`.
- Pivot tables use singular related model names in alphabetical order unless a richer join model is required.
- Status fields should use explicit constrained values, preferably represented in code by enums once implemented.

## Ownership And Access

Every domain table should define:

- Who owns the record.
- Which roles can create, read, update, and delete it.
- Whether records are user-scoped, team-scoped, tenant-scoped, or global.
- Whether changes require audit history.
- Whether soft deletes are required.

## Query Design

Before implementing a repeated query, decide whether it belongs in:

- An Eloquent relationship.
- A local scope.
- A dedicated query method.
- A reporting/read model.

Do not duplicate business filters across controllers, jobs, commands, and views.

## Migration Requirements

Each domain migration should document or imply:

- Required fields.
- Foreign keys and cascading behavior.
- Indexes for expected read paths.
- Unique constraints for business invariants.
- Safe defaults.
- Rollback behavior.

## Seed Data

Seeders should be used for stable reference data and local development fixtures. They should not create hidden business assumptions that tests or production behavior depend on without documentation.

## Open Data Questions

- What are the primary domain entities?
- Is the application single-tenant, team-scoped, or multi-tenant?
- Which records require audit trails?
- Which records require soft deletion?
- Which reports or dashboards are expected in the first production release?
