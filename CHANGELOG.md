# Changelog

## [2.0.2](https://github.com/Larasell-dev/extension-reviews/compare/v2.0.1...v2.0.2) (2026-10-31)


### Miscellaneous Chores

* enable the @larasell-dev/ui lint rules ([ec74c4a](https://github.com/Larasell-dev/extension-reviews/commit/ec74c4a6e6d480d4085d96b8bd029ed5321967b4))
* format with oxfmt ([c0d7e9a](https://github.com/Larasell-dev/extension-reviews/commit/c0d7e9a7a4351fadd004fca1ebc4473e71917f23))

## [2.0.1](https://github.com/Larasell-dev/extension-reviews/compare/v2.0.0...v2.0.1) (2026-10-31)


### Bug Fixes

* answer 400 to a request document that isn't a JSON object ([47b9f77](https://github.com/Larasell-dev/extension-reviews/commit/47b9f77810a95ba3167c913c04db72d62b151a29))
* answer 400 to an include path the API can't serve ([76473ff](https://github.com/Larasell-dev/extension-reviews/commit/76473ff66299206fe4772d85ec4dcd25e336c336))


### Miscellaneous Chores

* check the code with larasell-dev/phpstan-rules ([955f514](https://github.com/Larasell-dev/extension-reviews/commit/955f51420c420c2805e732b819cbefa40bf02ee0))

## [2.0.0](https://github.com/Larasell-dev/extension-reviews/compare/v1.0.1...v2.0.0) (2026-10-31)


### Features

* add a product review page ([691b8b2](https://github.com/Larasell-dev/extension-reviews/commit/691b8b2d376f4894ad270ea85f59bd218ae8b0f0))
* add product reviews ([283e1be](https://github.com/Larasell-dev/extension-reviews/commit/283e1beb9988562bc92fac33f221119c17458a28))
* approve and reject product reviews ([3e119b9](https://github.com/Larasell-dev/extension-reviews/commit/3e119b9631edc9b9b3132da79d700a9ce0b8a772))
* approve and reject reviews from the reviews table ([93fbf46](https://github.com/Larasell-dev/extension-reviews/commit/93fbf468befa6ef50184e58df92572eae1f1e415))
* create product reviews over JSON:API ([ca3ea5e](https://github.com/Larasell-dev/extension-reviews/commit/ca3ea5ebc5fa6e3f5878c3a7fcf105583b852b88))
* delete product reviews over JSON:API ([30b625e](https://github.com/Larasell-dev/extension-reviews/commit/30b625ec6b904386fe59b8fdc427820f7f0414a5))
* limit how many reviews one client can submit per minute ([642c4d0](https://github.com/Larasell-dev/extension-reviews/commit/642c4d0f78f9baebd262648c9a506ab77ed18671))
* link reviews table rows to their page ([3deaea6](https://github.com/Larasell-dev/extension-reviews/commit/3deaea6962050365d49777604cbd2e926ac3b56e))
* list and show product reviews over JSON:API ([28a9266](https://github.com/Larasell-dev/extension-reviews/commit/28a926618f9377f642d9d38f16c35d04f18777af))
* list product reviews in the admin panel ([e147fe5](https://github.com/Larasell-dev/extension-reviews/commit/e147fe55d7815d2fe752cfad45af2a103153d87f))
* require an API token for the product reviews API ([f7e723b](https://github.com/Larasell-dev/extension-reviews/commit/f7e723b697c3274fac9d9003fad0126db700818f))
* update product reviews over JSON:API ([507f537](https://github.com/Larasell-dev/extension-reviews/commit/507f5375ecd7519170a94d3907fea7d6e5dd6260))


### Bug Fixes

* require larasell-dev/admin 0.5.5 ([cd0623e](https://github.com/Larasell-dev/extension-reviews/commit/cd0623e9027983d8df3f038b4897d969ef0da87f))
* show the reviews table in apps with immutable dates ([a6de82c](https://github.com/Larasell-dev/extension-reviews/commit/a6de82cd0b531a4c456cecd8ee4bb45b4f289825))


### Miscellaneous Chores

* generate enum types with Wayfinder ([ae791a4](https://github.com/Larasell-dev/extension-reviews/commit/ae791a4904e75f44bb389de6bc3362e9d273f806))
* generate TypeScript types from Laravel Data ([de82436](https://github.com/Larasell-dev/extension-reviews/commit/de824367e429dba3ed4f974be68365dd65571e7b))
* open the playground on sample reviews ([feeac5e](https://github.com/Larasell-dev/extension-reviews/commit/feeac5e4ea8c786bf03f2d373672588826243925))
* require larasell-dev/admin 0.5.4 ([a56f084](https://github.com/Larasell-dev/extension-reviews/commit/a56f084c8ebd3a362b6808f0b4104cf55eb96799))
* require larasell-dev/chronicle ([ef52558](https://github.com/Larasell-dev/extension-reviews/commit/ef5255896262ba56d21ce6d3746df98f709151ce))

## [1.0.1](https://github.com/Larasell-dev/extension-reviews/compare/v1.0.0...v1.0.1) (2026-10-30)


### Miscellaneous Chores

* add pao ([1722d2c](https://github.com/Larasell-dev/extension-reviews/commit/1722d2cce30d8128008c45fc66204a62a5123bf3))
* add the admin panel, playground, CI checks and release automation ([03197f3](https://github.com/Larasell-dev/extension-reviews/commit/03197f3c27b09e347c5c638718e86f2ed2909376))
* install pint ([4ef5683](https://github.com/Larasell-dev/extension-reviews/commit/4ef568358288e8b4bbade326952cca29fb5ebcb4))
* make the TypeScript config stricter ([829728e](https://github.com/Larasell-dev/extension-reviews/commit/829728e62d42f8e7dd1f05773d97daa96ca823d7))
* remove everything to start from a clean state ([a79e1d4](https://github.com/Larasell-dev/extension-reviews/commit/a79e1d488f6edf58b2b9cf6a2d73aa5eb0416cbc))
* remove the placeholder comment from the config ([5b5805b](https://github.com/Larasell-dev/extension-reviews/commit/5b5805b58f70cf2e6fdd855ee9cb52aeb53666d6))
* rename test command to pest ([565f7de](https://github.com/Larasell-dev/extension-reviews/commit/565f7de2db4358fc895ab4d8368c3f910e1e65d6))
* set up the package with pint, larastan, pest and commitlint ([14f8640](https://github.com/Larasell-dev/extension-reviews/commit/14f86405f661e1464d62c3fe4e4ae22f0ba5be2f))
