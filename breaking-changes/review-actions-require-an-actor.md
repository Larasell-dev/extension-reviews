# The review moderation actions now require an actor

`ApproveProductReview`, `RejectProductReview`, `UpdateProductReview` and `DeleteProductReview`
now write an audit record to the admin panel's audit log, in the same transaction as the change.
The record says who made the change, so each action now takes that actor as a required argument.

## Who is affected

Apps that call these actions' `handle()` methods from their own code, such as a command, a job or
a listener. Changes made in the admin panel and through the API are not affected: they pass the
signed-in admin or the API token for you. `SubmitProductReview` is unchanged, since customer
submissions aren't audited.

## How to upgrade

Pass who made the change. In jobs and commands, use a `SystemActor` named for what the code does.
In your own admin or API code, pass the signed-in `Admin` or the request's `ApiToken`.

```php
use Larasell\Admin\Audit\SystemActor;

// Before
app(ApproveProductReview::class)->handle($review);
app(UpdateProductReview::class)->handle($review, ['rating' => 4]);

// After
app(ApproveProductReview::class)->handle($review, new SystemActor('spam-filter'));
app(UpdateProductReview::class)->handle($review, ['rating' => 4], new SystemActor('spam-filter'));
```

If the actor truly can't be known, pass an `UnknownActor` with the reason, e.g.
`new UnknownActor('Imported from the previous shop system.')`.

The actions now also throw `Larasell\Admin\Exceptions\AuditFailed` when the audit record can't be
stored. The review is then left unchanged and no event is dispatched.
