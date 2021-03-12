<?php

declare(strict_types=1);

namespace Sylapi\Courier\Inpost;

use Sylapi\Courier\Contracts\CourierMakeParcel;
use Sylapi\Courier\Contracts\Parcel;

class InpostCourierMakeParcel implements CourierMakeParcel
{
    public function makeParcel(): Parcel
    {
        return new InpostParcel();
    }
}


