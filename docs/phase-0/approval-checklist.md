# Approval Checklist

## Phase 0 Approval Status

Status: pending approval.

Phase 1 must not begin until this checklist is approved.

## Product Approval

- [ ] The project purpose is accurate.
- [ ] The primary goals are accurate.
- [ ] The listed assumptions are accepted or corrected.
- [ ] Phase 1's first vertical slice is identified.
- [ ] Non-goals are accepted.

## Architecture Approval

- [ ] Laravel conventions are accepted as the default architecture.
- [ ] Eloquent is accepted as the default data access layer.
- [ ] Repositories are not introduced by default.
- [ ] Services are introduced only for cohesive business workflows.
- [ ] Reuse-first rules are accepted.

## Data Approval

- [ ] Primary domain entities are identified or scheduled for definition.
- [ ] Ownership model is known or scheduled for definition.
- [ ] Tenancy model is known or scheduled for definition.
- [ ] Audit requirements are known or scheduled for definition.
- [ ] Soft delete expectations are known or scheduled for definition.

## Security Approval

- [ ] Authentication expectations are known.
- [ ] Authorization expectations are known.
- [ ] Sensitive data expectations are known.
- [ ] Audit and logging expectations are known.
- [ ] External integration risks are known or not applicable.

## Testing Approval

- [ ] Pest is accepted as the default test framework.
- [ ] Feature tests are required for HTTP workflows.
- [ ] Authorization and validation tests are required for protected workflows.
- [ ] Regression tests are required for bug fixes.
- [ ] Quality gates are accepted.

## Operations Approval

- [ ] Environment strategy is accepted.
- [ ] Configuration strategy is accepted.
- [ ] Deployment expectations are accepted.
- [ ] Backup and recovery requirements are scheduled before production launch.
- [ ] Observability requirements are scheduled before production launch.

## Required Decisions Before Phase 1

- [ ] What is the first user-facing workflow?
- [ ] Who are the actors or roles?
- [ ] What records must the workflow create, read, update, or delete?
- [ ] What authorization rules apply?
- [ ] What does successful completion look like?

## Approval Statement

Phase 0 is approved when the above checklist is reviewed and the required decisions before Phase 1 are answered.
