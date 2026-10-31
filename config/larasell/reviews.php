<?php

return [

    'api' => [

        /*
         * Middleware of the routes under /api/product-reviews. larasell.api requires an API token
         * that an admin creates under Settings > API tokens, sent as `Authorization: Bearer <token>`.
         */
        'middleware' => ['api', 'larasell.api'],

        /*
         * How many reviews one client can submit per minute: one API token, or one IP address if
         * the middleware above authenticates no one. Further submissions get a 429.
         */
        'submissions_per_minute' => 10,

    ],

];
