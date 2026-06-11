# Operations

## Environment Strategy

The application should support distinct local, test, staging, and production environments.

Each environment must define:

- Application URL.
- Database connection.
- Cache store.
- Queue connection.
- Session driver.
- Mail transport.
- File storage disk.
- Logging channel.
- Third-party credentials when integrations are approved.

## Configuration

- Use Laravel configuration files as the application-facing configuration layer.
- Use environment variables for deployment-specific values.
- Do not read environment variables directly from application code outside configuration files.
- Keep `.env.example` updated when new configuration values are introduced.

## Deployment Expectations

Production deployments should include:

- Dependency installation.
- Configuration caching.
- Route and view caching where appropriate.
- Database migration review.
- Queue worker restart.
- Asset build.
- Smoke test after deployment.

## Database Operations

Before production migrations:

- Review destructive operations.
- Confirm rollback strategy.
- Confirm indexes for expected read paths.
- Confirm foreign key behavior.
- Confirm data backfill strategy if existing data is affected.

## Queue Operations

Queued work should define:

- Queue name.
- Retry behavior.
- Timeout behavior.
- Idempotency expectations.
- Failure handling.
- Monitoring expectations.

## Observability

The production application should provide:

- Structured application logs.
- Error reporting.
- Health checks.
- Queue failure visibility.
- Slow query visibility.
- Deployment traceability.

## Backups And Recovery

Before production launch:

- Define database backup frequency.
- Define retention period.
- Define restore process.
- Test restore on a non-production environment.
- Document file storage backup expectations if user-uploaded files are introduced.

## Maintenance

Maintenance work should include:

- Dependency updates.
- Security patch review.
- Log and storage cleanup.
- Queue failure review.
- Database growth review.
- Documentation updates when operational behavior changes.
