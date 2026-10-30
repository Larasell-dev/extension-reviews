# Larasell Reviews

Product reviews for [Larasell](https://larasell.dev).

## Installation

```bash
composer require larasell-dev/reviews
php artisan migrate
php artisan larasell:link
```

## Development

```bash
composer test       # Pest
composer phpstan    # Larastan
composer lint       # Pint
npm run commitlint  # Commitlint on the last commit
npm run typecheck   # TypeScript
npm run build       # The admin assets, into public/vendor/larasell-reviews
npm run dev         # The admin assets with live reloading
```

Admin pages are React components registered with `Larasell.booting()` in `resources/js/admin.ts`.
`npm run build` bundles them with the panel's `larasell()` Vite plugin. The panel loads them through
`AdminPanel::vite()`, and `larasell:link` links the bundle into the app.

Breaking changes get an upgrade note in `breaking-changes/` and a `!` in the pull request title, e.g.
`feat!: ...`; see `breaking-changes/AGENT.md`. CI checks that both go together.

The build isn't committed on `main`. The release workflow builds it on release-please's release
branch, so each release tag ships it, and CI rejects other pull requests that change it.

The playground serves the admin panel with this extension installed (`vendor/bin/testbench serve`);
`.agents/setup` prepares it.
