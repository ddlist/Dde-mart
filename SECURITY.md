# Security Policy

## Reporting a vulnerability

Do NOT open a public issue. Email **shariqq.com@gmail.com** with:

- Affected repo and version/commit
- Description and impact (auth bypass, data leak, injection, …)
- Reproduction steps or proof of concept (test accounts only — never
  probe production systems you don't own)

Expect an acknowledgment within 72 hours and a fix or mitigation plan
once confirmed. Coordinated disclosure: please allow time to patch and
release before publishing details.

## Scope notes

- OTP codes are log-driver outside production by design; production
  requires an SMS gateway + `APP_ENV=production`.
- Unconfigured payment gateways return honest 422s by design.
- Seeded demo credentials (`admin@email.com / 12345678`) must be changed
  on first login — production checklists in the README cover this.
