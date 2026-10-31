import { Larasell } from "@larasell-dev/admin";

Larasell.booting(({ pages }) => {
  pages.register(
    "larasell-reviews::ProductReview/Show",
    () => import("./Pages/ProductReview/Show"),
  );
});
