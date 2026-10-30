import { defineConfig } from "vite";

// Builds into public/vendor/larasell-reviews, which ReviewsServiceProvider registers with
// AdminPanel::vite() under the same name. React, Inertia, @larasell-dev/ui and
// @larasell-dev/admin come from the panel at runtime, so the bundle holds only this extension's code.
import { larasell } from "./vendor/larasell-dev/admin/resources/vite/plugin.ts";

export default defineConfig({
  plugins: [
    larasell({
      name: "larasell-reviews",
      input: ["resources/js/admin.ts", "resources/css/admin.css"],
    }),
  ],
});
