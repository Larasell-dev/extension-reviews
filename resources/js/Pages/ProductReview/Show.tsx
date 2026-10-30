import { Link, router } from "@inertiajs/react";
import { Badge, Button, Icon, Inset, Section } from "@larasell-dev/ui";
import { StarFill, StarLine } from "@larasell-dev/ui/icons";
import { Page, SidebarLayout } from "@larasell-dev/admin";
import type { ReactNode } from "react";

import { ProductReviewStatus } from "../../wayfinder/Larasell/Reviews/Enums/ProductReviewStatus";

type Props = Larasell.Reviews.Data.View.ProductReviewPageData;
type LinkData = Larasell.Reviews.Data.ViewModel.LinkData;

/** One review: what the customer wrote, and the actions that approve or reject it. */
export default function Show({ review, indexUrl, moderation }: Props) {
  const post = (url: string) => router.post(url, {}, { preserveScroll: true });

  return (
    <>
      <Page.Header
        title={`Review of ${review.product.name}`}
        backLink={{ href: indexUrl, label: "Back to reviews" }}
        actions={
          <div className="flex items-center gap-2">
            {moderation.status !== ProductReviewStatus.Rejected && (
              <Button variant="secondary" size="sm" onClick={() => post(moderation.rejectUrl)}>
                Reject
              </Button>
            )}
            {moderation.status !== ProductReviewStatus.Approved && (
              <Button variant="primary" size="sm" onClick={() => post(moderation.approveUrl)}>
                Approve
              </Button>
            )}
          </div>
        }
      />
      <Page.Body>
        <div className="flex flex-col gap-6">
          <div className="flex items-center gap-2 type-body-md text-mauve-600 dark:text-mauve-400">
            <Badge variant={review.status.variant}>{review.status.label}</Badge>
            {review.submittedAt && <span>{review.submittedAt}</span>}
          </div>
          <Section.Root>
            <Section.Header title="Review" />
            <Section.Card>
              <Inset>
                <div className="flex flex-col gap-3">
                  <Stars rating={review.rating} max={review.maxRating} />
                  {review.comment ? (
                    <p className="type-body-md whitespace-pre-line text-mauve-950 dark:text-mauve-50">
                      {review.comment}
                    </p>
                  ) : (
                    <Muted>No comment</Muted>
                  )}
                </div>
              </Inset>
            </Section.Card>
          </Section.Root>
          <Section.Root>
            <Section.Header title="Details" />
            <Section.Card>
              <Inset>
                <dl className="grid gap-4 type-body-md wrap-anywhere sm:grid-cols-3">
                  <Field label="Product">
                    <MaybeLink item={review.product} />
                  </Field>
                  <Field label="Name">{review.name}</Field>
                  <Field label="Customer">
                    {review.customer ? <MaybeLink item={review.customer} /> : <Muted>Guest</Muted>}
                  </Field>
                </dl>
              </Inset>
            </Section.Card>
          </Section.Root>
        </div>
      </Page.Body>
    </>
  );
}

function Stars({ rating, max }: { rating: number; max: number }) {
  return (
    <div className="flex items-center gap-0.5 text-amber-500" role="img" aria-label={`${rating} out of ${max} stars`}>
      {Array.from({ length: max }, (_, index) => (
        <Icon key={index} icon={index < rating ? StarFill : StarLine} />
      ))}
    </div>
  );
}

function MaybeLink({ item }: { item: LinkData }) {
  if (!item.url) {
    return <>{item.name}</>;
  }

  return (
    <Link href={item.url} prefetch="hover" className="underline underline-offset-2">
      {item.name}
    </Link>
  );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex min-w-0 flex-col gap-0.5">
      <dt className="type-label-sm text-mauve-600 dark:text-mauve-400">{label}</dt>
      <dd className="text-mauve-950 dark:text-mauve-50">{children}</dd>
    </div>
  );
}

function Muted({ children }: { children: ReactNode }) {
  return <div className="type-body-md text-mauve-600 dark:text-mauve-400">{children}</div>;
}

Show.layout = [SidebarLayout];
