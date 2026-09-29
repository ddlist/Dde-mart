# Contributing to DDE-Mart

Thanks for helping improve DDE-Mart. This project uses a custom
source-available license ([LICENSE](LICENSE)) — by contributing you agree
your work is licensed under the same terms.

## Workflow

1. Fork the repo, create a branch from `master` (backend) or `main`/`master`
   per app repo.
2. Backend: run `php artisan test` — the suite must stay green
   (238 tests / ~1400 assertions). Apps: `flutter analyze` clean +
   `flutter test` green.
3. Follow existing conventions: no hardcoded colors/strings off-theme in
   apps, server-computed reports, permission flows before device features.
4. Regenerate API docs when routes change: `php artisan ddemart:api-docs`.
5. Open a pull request describing what and why. One feature per PR.

## Don'ts

- Never commit `.env`, service-account keys, `google-services.json`,
  `GoogleService-Info.plist`, `*.sql` dumps, keystores, or secrets.
- No real customer data in tests or fixtures — factories only.
- Don't break the license: keep "Built by DDLIST" credits intact.

## Reporting bugs

Open an issue with: repo + version/commit, steps to reproduce, expected
vs actual, logs/screenshots. Security issues: see [SECURITY.md](SECURITY.md)
— do NOT open public issues for vulnerabilities.
