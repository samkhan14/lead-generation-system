# Project Brief

## Purpose

This project is a Laravel + Inertia + Vue CRM application focused first on **Lead Intelligence** — capturing leads, scoring them, and supporting sales workflows — before expanding into deals, clients, and projects.

The application should grow from a clean foundation into a production-ready, maintainable business system without premature module sprawl.

Phase 0 defines the project baseline before feature implementation begins. The goal is to prevent duplicated business logic, premature abstractions, unclear ownership, and undocumented architectural decisions.

## Current State

The repository currently contains a fresh Laravel application skeleton with:

- Default web routing.
- Default authentication-ready `User` model.
- Default database migrations for users, password resets, sessions, cache, and queues.
- Pest test scaffolding.
- Vite and Tailwind CSS build tooling.

No project-specific application code exists yet.

## Primary Goals

- Build a maintainable Laravel application with clear domain boundaries.
- Keep business rules centralized and testable.
- Use Laravel conventions unless there is a strong reason to introduce additional structure.
- Avoid duplicate services, helpers, repositories, middleware, traits, queries, and components.
- Establish documentation before implementation so future work has an agreed direction.
- Preserve room for production needs such as authentication, authorization, auditability, observability, and automated testing.

## Assumptions Requiring Approval

- The application will use Laravel's default MVC structure as the starting point.
- Feature modules will be introduced only when domain complexity justifies them.
- Server-rendered Blade views are acceptable initially unless a SPA or hybrid frontend is explicitly approved later.
- Eloquent will be the default data access layer; repositories will not be added by default.
- Pest will be the default test runner.
- The first implementation phase will focus on one approved vertical slice rather than broad scaffolding.

## Non-Goals For Phase 0

- No application features.
- No database schema beyond the existing Laravel defaults.
- No authentication scaffolding changes.
- No UI components.
- No external service integrations.
- No speculative service, repository, helper, middleware, or trait creation.

## Success Definition

Phase 0 succeeds when the team has enough shared context to begin Phase 1 without inventing architecture during implementation.
