# Testing Strategy

## Test Framework

Use Pest with Laravel's testing utilities.

The repository already includes unit and feature test scaffolding. Future test structure should follow Laravel conventions unless the application becomes large enough to justify feature-specific organization.

## Testing Pyramid

- Unit tests for pure business rules, value calculations, and isolated domain behavior.
- Feature tests for HTTP workflows, validation, authorization, persistence, and user-visible outcomes.
- Integration tests for external services, queues, mail, notifications, file storage, and scheduled work.
- Browser or end-to-end tests only for critical user journeys where lower-level tests cannot provide confidence.

## Required Coverage By Change Type

- Controller or route changes require feature tests.
- Validation changes require success and failure tests.
- Authorization changes require allowed and denied tests.
- Model relationship or query changes require tests that prove the intended records are included and excluded.
- Jobs require tests for dispatch behavior and handler behavior.
- Events and listeners require tests for dispatch and side effects.
- Migrations require tests when they encode business constraints or non-trivial relationships.

## Test Data

- Use factories for generated data.
- Use seeders for stable reference data only.
- Avoid brittle tests that depend on global database state.
- Prefer explicit test setup over hidden fixture coupling.

## Quality Gates

Before a feature is considered complete:

- Tests must pass.
- New behavior must have focused coverage.
- Known edge cases must be covered or documented.
- Authorization and validation failures must be tested.
- Duplicate business logic introduced during implementation must be removed.

## Regression Strategy

When fixing a bug:

- Add a failing test that reproduces the issue.
- Fix the smallest responsible behavior.
- Keep the regression test focused on the broken contract.

## Performance Testing

For features with large datasets or dashboards:

- Define expected data volume.
- Confirm pagination or streaming behavior.
- Check for N+1 queries.
- Add query assertions or targeted tests when practical.

## Manual Testing

Manual testing should be documented for workflows that include:

- Complex browser interactions.
- File uploads.
- Email verification.
- Payment or external provider callbacks.
- Administrative workflows.
