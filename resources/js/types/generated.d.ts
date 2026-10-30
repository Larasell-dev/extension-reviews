declare namespace Larasell.Reviews.Data.View {
  export type ProductReviewPageData = {
    review: Larasell.Reviews.Data.ViewModel.ProductReviewDetailData;
    indexUrl: string;
    moderation:
      | Larasell.Reviews.Data.ViewModel.Moderation.PendingModerationData
      | Larasell.Reviews.Data.ViewModel.Moderation.ApprovedModerationData
      | Larasell.Reviews.Data.ViewModel.Moderation.RejectedModerationData;
  };
}
declare namespace Larasell.Reviews.Data.ViewModel {
  export type BadgeData = {
    label: string;
    variant: import("@larasell-dev/ui").BadgeVariant;
  };
  export type LinkData = {
    name: string;
    url: string | null;
  };
  export type ProductReviewDetailData = {
    name: string;
    rating: number;
    maxRating: number;
    comment: string | null;
    status: Larasell.Reviews.Data.ViewModel.BadgeData;
    submittedAt: string | null;
    product: Larasell.Reviews.Data.ViewModel.LinkData;
    customer: Larasell.Reviews.Data.ViewModel.LinkData | null;
  };
}
declare namespace Larasell.Reviews.Data.ViewModel.Moderation {
  export type ApprovedModerationData = {
    status: typeof import("../wayfinder/Larasell/Reviews/Enums/ProductReviewStatus").Approved;
    rejectUrl: string;
  };
  export type PendingModerationData = {
    status: typeof import("../wayfinder/Larasell/Reviews/Enums/ProductReviewStatus").Pending;
    approveUrl: string;
    rejectUrl: string;
  };
  export type RejectedModerationData = {
    status: typeof import("../wayfinder/Larasell/Reviews/Enums/ProductReviewStatus").Rejected;
    approveUrl: string;
  };
}
