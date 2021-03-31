<?php

declare(strict_types=1);

namespace Sylapi\Courier\Inpost;

use Rakit\Validation\Validator;
use Sylapi\Courier\Abstracts\Sender;

class InpostSender extends Sender
{
    public function validate(): bool
    {
        $rules = [
            'fullName'    => 'required',
            'address'     => 'required',
            'countryCode' => 'required|min:2|max:2',
            'city'        => 'required',
            'zipCode'     => 'required',
            'email'       => 'nullable|email',
            'phone'       => 'required',
        ];

        $data = $this->toArray();

        $validator = new Validator();

        $validation = $validator->validate($data, $rules);
        if ($validation->fails()) {
            $this->setErrors($validation->errors()->toArray());

            return false;
        }

        return true;
    }
}