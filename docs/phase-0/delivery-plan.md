# Delivery Plan

## Phase Model

This project should advance through explicit phases. Each phase requires approval before implementation begins.

## Phase 0: Documentation And Baseline

Goal: establish shared direction before application code is created.

Deliverables:

- Project brief.
- Requirements baseline.
- Architecture baseline.
- Data modeling baseline.
- Security baseline.
- Testing strategy.
- Delivery and operations baseline.
- Agent implementation rules.
- Approval checklist.

Exit gate: approval of Phase 0 documentation.

## Setup Phase (Before / During Phase 1)

Goal: rules + base structure.

Tasks:

- Laravel install (done).
- Inertia + Vue setup.
- Define folder architecture.
- Setup base layout.
- Setup `.env` config.
- Setup logging.
- Setup queue (optional but prepared).

See `docs/phase-1/README.md` for detail.

## Phase 1: Core Database (CRM + Lead Intelligence)

Goal: lean core persistence only — not the full CRM database.

Approved tables:

- `users` (existing Laravel baseline).
- Roles and permissions (Spatie).
- `leads`.
- Lead score fields on `leads`, or `lead_scores` if score history is approved.

Not allowed in Phase 1:

- Deals (full system).
- Clients (full system).
- Projects (full system).

Expected deliverables:

- Migrations, models, factories, seeders, and policies for in-scope tables.
- Spatie roles/permissions seeded.
- Focused tests for schema, authorization, and relationships.
- Optional minimal Inertia UI only if explicitly approved.

Exit gate: core tables migrated, seeded, tested; no out-of-scope modules.

Full plan: `docs/phase-1/README.md`.

## Phase 2: Auth + Admin Foundation

Goal: system usable internally.

Deliverables:

- Admin layout with sidebar
- Basic dashboard with lead stats
- Shared auth context (roles, permissions) for frontend
- Internal-only access (no public registration)

Full plan: `docs/phase-2/README.md`.

Exit gate: authenticated users can log in, see dashboard, and navigate via admin sidebar.

## Phase 3: Core Domain Expansion

Goal: expand the core domain with additional approved workflows.

Expected deliverables:

- Reused patterns from Phase 1.
- Shared services or scopes only where duplication has become real.
- Broader authorization and validation coverage.
- Documentation updates for new domain concepts.

Exit gate: the core domain supports the agreed MVP workflows.

## Phase 3: Production Hardening

Goal: prepare for reliable production use.

Expected deliverables:

- Observability and logging review.
- Queue and scheduler review.
- Performance review for high-traffic workflows.
- Security review.
- Backup and restore plan.
- Deployment checklist.

Exit gate: production readiness approval.

## Change Control

Any change that affects architecture, persistence, security, or delivery sequencing must be documented before implementation.

## Work Intake Template

Each new implementation request should include:

- Problem statement.
- Actor or user role.
- Desired outcome.
- Acceptance criteria.
- Data changes.
- Authorization rules.
- Test expectations.
- Known constraints.

## Approval Rule

Do not begin Phase 1 implementation until Phase 0 is approved.
