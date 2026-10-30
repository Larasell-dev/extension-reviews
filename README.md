# Larasell Reviews

Product reviews for [Larasell](https://larasell.dev).

## Installation

```bash
composer require larasell-dev/reviews
php artisan migrate
php artisan larasell:link
```

The admin panel gets a **Reviews** page below Products. It lists every review with its
status (pending, approved or rejected) and lets admins approve or reject each one. Each row
opens the review on its own page.

## Usage

```php
use Larasell\Reviews\Actions\SubmitProductReview;

// A guest review; the name is required.
app(SubmitProductReview::class)->handle($product, rating: 5, name: 'Alex', comment: 'Great lamp.');

// A customer's review copies their name, so it stays when the customer is deleted.
app(SubmitProductReview::class)->handle($product, rating: 4, customer: $customer);
```

New reviews are pending. `ApproveProductReview` and `RejectProductReview` change their status.

## API

A JSON:API 1.1 API under `/api/product-reviews` lists, shows, creates, updates (`PATCH`) and
deletes reviews.

## Events

| Event                    | When                   |
| ------------------------ | ---------------------- |
| `ProductReviewSubmitted` | A review is submitted. |
| `ProductReviewApproved`  | A review is approved.  |
| `ProductReviewRejected`  | A review is rejected.  |
| `ProductReviewUpdated`   | A review's rating, name or comment changes. |
| `ProductReviewDeleted`   | A review is deleted.   |

All live in `Larasell\Reviews\Events`, carry the `$review`, and dispatch after the transaction commits.

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

The playground serves the admin panel with sample reviews (`vendor/bin/testbench serve`);
`.agents/setup` prepares it.
