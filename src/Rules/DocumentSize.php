<?php

namespace GhostZero\Kvdb\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class DocumentSize implements ValidationRule
{
    public function __construct(protected int $allocatedSize)
    {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // value may not larger than 16KB

        if (strlen(json_encode($value)) > $this->allocatedSize) {
            $fail("The $attribute may not be greater than {$this->allocatedSize} bytes.");
        }
    }
}
