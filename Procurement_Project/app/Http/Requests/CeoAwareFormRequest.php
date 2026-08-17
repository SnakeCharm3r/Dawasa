<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class CeoAwareFormRequest extends FormRequest
{
    protected function passesAuthorization(): bool
    {
        if ($this->user()?->hasRole('ceo')) {
            return true;
        }

        return parent::passesAuthorization();
    }
}
