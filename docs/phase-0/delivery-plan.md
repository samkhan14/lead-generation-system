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

## Phase 1: First Vertical Slice

Goal: implement one complete, approved workflow from route to persistence and tests.

Expected deliverables:

- Feature specification with acceptance criteria.
- Minimal database changes required for the workflow.
- Routes, controllers, requests, policies, models, views, and tests as needed.
- No speculative abstractions.

Exit gate: the first workflow is usable, tested, and reviewed.

## Phase 2: Core Domain Expansion

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
