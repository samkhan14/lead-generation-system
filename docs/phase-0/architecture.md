# Architecture

## Architecture Principle

Use Laravel conventions first. Add custom layers only when they reduce real complexity, protect a boundary, or match a proven project pattern.

## Baseline Stack

- Laravel 13 for HTTP, routing, validation, queues, events, notifications, configuration, and persistence.
- PHP 8.3 or newer.
- Eloquent ORM for primary data access.
- Blade, Vite, and Tailwind CSS for the initial UI path unless a different frontend architecture is approved.
- Pest for automated tests.

## Recommended Application Structure

The application should start with Laravel's default structure:

- Controllers receive requests, coordinate validation, call domain/application code, and return responses.
- Form requests should be introduced when validation or authorization becomes reusable or non-trivial.
- Models own relationships, casts, local scopes, and simple model-level invariants.
- Policies own authorization decisions for models and domain actions.
- Jobs own queued work.
- Events and listeners should be introduced when behavior needs decoupling.
- Services should be introduced only for cohesive business workflows that do not belong in a model, controller, job, or Laravel-native facility.

## Reuse-First Rule

Before creating any service, helper, trait, component, middleware, repository, model scope, query object, job, event, listener, policy, or request class:

- Search the codebase for an existing implementation.
- Check whether existing functionality can be extended safely.
- Confirm the new abstraction has a single clear responsibility.
- Confirm it will be used now, not reserved for speculative future use.

## Repositories

Repositories should not be added by default.

Use Eloquent models, relationships, query scopes, builders, and dedicated query methods first. Introduce repositories only if there is a concrete persistence boundary, multiple backing stores, or a repeated query contract that Eloquent cannot express cleanly.

## Services

Service classes are acceptable when they coordinate a complete business action, such as completing a workflow, synchronizing external data, or enforcing rules across multiple models.

Services should not become generic dumping grounds. A good service has:

- A specific domain name.
- A small public API.
- Explicit input and output.
- Tests at the behavior level.

## Queries

Reusable query logic should live close to the data it describes:

- Model relationships for related data.
- Local scopes for reusable filters.
- Query builder methods for complex reusable read paths.
- Dedicated read models only when reporting complexity justifies them.

Duplicate query filters are not allowed once reuse is practical.

## Middleware

Middleware should be reserved for cross-cutting HTTP concerns such as authentication context, tenant resolution, request normalization, security headers, or rate limiting.

Middleware must not contain feature-specific business logic.

## Components

Blade components should be introduced for repeated UI patterns with stable responsibilities.

Avoid componentizing one-off markup during early implementation. Prefer clarity until duplication is real.

## Dependency Direction

High-level business workflows should not depend on presentation details. Controllers and views may depend on application/domain behavior, but domain behavior must not depend on controllers, requests, or views.

## Configuration

Environment-specific values must use Laravel configuration files and `.env` values. Application code should consume values through `config()` instead of reading environment variables directly outside configuration files.

## Architecture Decision Records

Create an ADR when a decision:

- Changes a major technology choice.
- Adds a new architectural layer.
- Introduces an external dependency.
- Changes persistence strategy.
- Affects security, authorization, or deployment.
