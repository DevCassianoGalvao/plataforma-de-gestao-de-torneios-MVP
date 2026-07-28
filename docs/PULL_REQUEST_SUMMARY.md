# Product Reconstruction - Review Summary

## Status

**BLOCKED: do not open or merge a Pull Request yet.** The final regression run fails at `tests/public_portal_e2e.php` with `Portal unavailable copa-brasil-de-talentos-2026`.

## Original problem

The product used a generic CRUD and a generic public portal, exposing technical concepts in end-user flows and relying on an overlapping CSS cascade.

## Reconstruction architecture

- Role-aware product navigation through `ProductNavigationService` and `ProductNavigationController`.
- Dedicated assisted-management, competition and match-workspace controllers/views.
- Public presenter boundary using explicit public fields rather than `SELECT *` for content.
- CSS cascade removes `app.css` from the active load while keeping it as a rollback artifact.

## Modules and pages changed

- Assisted registration: teams, athletes, staff, guardians, registrations, documents and regulation settings.
- Competition: groups, rounds, fixtures, standings and bracket entry points.
- Match workspace: details, lineup, operation, homologation, rectification and report entry points.
- Public: separate home, fixtures, standings and bracket templates.

## Backend preservation

Sports services, authorization, scopes, migrations and audit services remain the system of record. The reconstruction adds presentation/controller adapters rather than replacing sports calculations.

## CSS changes

- Active cascade: tokens, themes, foundation, layout, components and scoped page files.
- `app.css` no longer loads globally; no legacy stylesheet was deleted.

## Test evidence

Passed in the latest run: `integration.php`, `authorization_e2e.php`, `sports_rules_e2e.php`, `rectification_e2e.php`, `accountability_e2e.php`.

Failed: `public_portal_e2e.php`. HTTP navigation and management tests were not executed after this stopping failure and must be rerun after the portal regression is fixed.

## Risks and limitations

- Stages 2 to 5 remain incomplete against their acceptance criteria.
- Portal route/view compatibility is currently broken by the partial template split.
- Browser responsive/a11y validation and end-to-end HTTP coverage remain incomplete.

## Deployment and rollback

Do not deploy this branch. If deployed in a non-production environment, rollback by redeploying the previous `main` commit; no destructive migration was introduced by the reconstruction beyond additive `020_assisted_document_metadata.sql`.

## Manual checklist after fixing the blocker

- [ ] Run all service and HTTP regressions.
- [ ] Verify login and role landing under `/copa-online/`.
- [ ] Verify public championship slug, fixtures, standings and bracket.
- [ ] Verify private-document denial and authorized download.
- [ ] Verify desktop/mobile layouts and light/dark themes in a browser.
- [ ] Confirm no `.env`, dumps, private uploads, logs or generated PDFs are staged.
