# Agent Implementation Rules

## Scope

These rules apply to future AI-assisted implementation work in this repository.

## Current Phase Rule

Phase 0 is documentation-only. Do not generate application code during Phase 0.

## Read Before Writing

Always analyze existing code before creating new files.

Before creating any service, helper, trait, component, middleware, repository, query abstraction, job, event, listener, policy, request class, model, controller, command, or migration:

- Search the codebase.
- Identify any existing reusable implementation.
- Extend existing functionality when appropriate.
- Create new code only when reuse is not a good fit.

## Reuse First

Prefer extending existing functionality over creating duplicate implementations.

Avoid:

- Dead code.
- Duplicate business logic.
- Duplicate queries.
- Speculative abstractions.
- Generic helpers without a stable use case.
- Repositories that only wrap Eloquent without adding a real boundary.

## Laravel Convention First

Use Laravel-native tools before custom architecture:

- Validation through Laravel validation and form requests.
- Authorization through policies and gates.
- Persistence through Eloquent models, relationships, scopes, and migrations.
- Async work through jobs and queues.
- Cross-cutting HTTP behavior through middleware.
- Notifications through Laravel notifications.
- Events and listeners for decoupled side effects.

## File Creation Criteria

Create a new file only when:

- The responsibility is clear.
- Existing code cannot reasonably own the behavior.
- The file will be used immediately.
- The abstraction improves maintainability.
- Tests or documentation can describe its behavior.

## Business Logic Placement

- Keep controllers thin.
- Keep validation out of views.
- Keep authorization out of duplicated controller branches.
- Keep repeated query filters in scopes, relationships, or dedicated query methods.
- Keep complex workflows in focused domain/application services only when needed.

## Testing Rule

Every behavior change should include focused tests unless explicitly deferred with approval.

Tests should prove:

- Successful behavior.
- Validation failures.
- Authorization failures.
- Important edge cases.
- Persistence changes when applicable.

## Documentation Rule

Update documentation when a change affects:

- Product scope.
- Architecture.
- Data model.
- Security.
- Operations.
- Delivery plan.
- Public setup or usage instructions.

## Stop Conditions

Stop and ask for approval when:

- Requirements are ambiguous.
- A new architecture layer seems necessary.
- A feature would duplicate existing behavior.
- A migration may be destructive.
- Security expectations are unclear.
- The requested change conflicts with Phase 0 or approved documentation.
