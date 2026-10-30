<?php

namespace Larasell\Reviews\Enums;

use Larasell\Admin\Enums\Table\Variant;

enum ProductReviewStatus: string
{
    /** Waiting for an admin to approve or reject it. New reviews start here. */
    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /** The colour of the status's badge in the admin panel. */
    public function variant(): Variant
    {
        return match ($this) {
            self::Pending => Variant::Warning,
            self::Approved => Variant::Success,
            self::Rejected => Variant::Danger,
        };
    }
}
