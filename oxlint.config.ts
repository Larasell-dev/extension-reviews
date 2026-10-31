import { recommended } from "@larasell-dev/ui/eslint";
import { defineConfig } from "oxlint";

export default defineConfig({
  extends: [recommended],
  ignorePatterns: ["public/**", "vendor/**", "workbench/**", "resources/js/wayfinder/**"],
});
