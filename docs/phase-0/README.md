# Phase 0 Documentation

Phase 0 establishes the product, architecture, delivery, and governance baseline for this Laravel application before application code is generated.

## Status

Current status: pending approval.

No application code should be created until these documents are reviewed and approved.

## Repository Baseline

- Framework: Laravel 13 application skeleton.
- Runtime: PHP 8.3 or newer.
- Frontend build: Vite with Tailwind CSS.
- Test framework: Pest with Laravel integration.
- Existing application surface: default web route, default user model, default Laravel migrations for users, sessions, cache, and queues.

## Phase 0 Documentation Set

- `project-brief.md` defines the product goals, assumptions, scope, and non-goals.
- `requirements.md` defines functional and non-functional requirements.
- `architecture.md` defines the target architecture and code organization rules.
- `data-model.md` defines the initial data modeling principles and planned domain areas.
- `security.md` defines authentication, authorization, data protection, and audit requirements.
- `testing-strategy.md` defines quality gates and test coverage expectations.
- `delivery-plan.md` defines the phased implementation plan and approval gates.
- `operations.md` defines environment, deployment, observability, and maintenance expectations.
- `agent-rules.md` defines implementation rules for future AI-assisted development.
- `approval-checklist.md` defines the explicit approval gate before Phase 1 begins.

## Phase 0 Exit Criteria

Phase 0 is complete only when:

- The product assumptions are accepted or corrected.
- The initial technical architecture is approved.
- Data ownership and security expectations are agreed.
- The delivery plan is approved.
- The Phase 1 scope is confirmed.

## Important Constraint

This phase intentionally produces documentation only. It does not add services, controllers, migrations, models, views, jobs, middleware, components, repositories, helpers, traits, or other application code.
