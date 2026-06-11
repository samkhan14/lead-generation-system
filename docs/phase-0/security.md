# Security

## Security Baseline

Security decisions must be explicit before implementing user-facing workflows. Laravel defaults should be used where possible, and custom security logic should be avoided unless requirements demand it.

## Authentication

- Use Laravel-supported authentication patterns.
- Passwords must always use Laravel hashing.
- Authentication state must use secure session handling.
- Email verification should be required when workflows depend on verified identity.
- Password reset flows must use Laravel token handling.

## Authorization

- Use policies and gates for authorization.
- Controllers must not contain duplicated authorization branches when a policy can represent the rule.
- Authorization must be tested for sensitive workflows.
- Every protected model or domain action must define allowed actors.

## Input Validation

- Validate all user-controlled input.
- Prefer form requests for reusable or complex validation.
- Validate file uploads by type, size, and storage path before accepting them.
- Never trust client-provided identifiers without checking ownership or access.

## Data Protection

- Secrets must not be stored in source control.
- Sensitive configuration must come from environment-specific configuration.
- Personally identifiable information must be minimized.
- Logs must not include passwords, tokens, session payloads, or secret values.
- Encryption should be used for sensitive persisted fields when business requirements justify it.

## Session And Cookie Security

- Production sessions must use secure cookie settings.
- SameSite settings must match the deployment and authentication model.
- Session lifetime should balance security with user experience.
- Privileged actions may require re-authentication if the risk profile demands it.

## Rate Limiting

Rate limits should protect:

- Login attempts.
- Password reset requests.
- Email or notification sending.
- Public forms.
- Any future API endpoints.

## Auditability

Audit records should be considered for:

- Authentication and account changes.
- Permission or role changes.
- High-value domain record changes.
- Data exports.
- Administrative actions.

## External Integrations

Before adding an external service:

- Document the data shared with the provider.
- Define failure behavior and retries.
- Confirm where credentials are stored.
- Confirm logging does not expose provider secrets or customer data.

## Security Review Gate

No feature that handles authentication, authorization, money, private data, file uploads, or external integrations should move beyond implementation without a security review.
