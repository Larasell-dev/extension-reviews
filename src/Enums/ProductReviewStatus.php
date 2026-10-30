<?php

namespace Larasell\Reviews\Enums;

enum ProductReviewStatus: string
{
    /** Waiting for an admin to approve or reject it. New reviews start here. */
    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';
}
