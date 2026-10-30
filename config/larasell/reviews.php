<?php

return [

    'api' => [

        /*
         * Middleware of the routes under /api/product-reviews. They have no authentication as is;
         * add your API's, for example ['api', 'auth:sanctum'], before going live.
         */
        'middleware' => ['api'],

        /*
         * How many reviews one client can submit per minute: one user once the API has
         * authentication, one IP address until then. Further submissions get a 429.
         */
        'submissions_per_minute' => 10,

    ],

];
