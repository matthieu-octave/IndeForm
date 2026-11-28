<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Adresse https uniquement, avec un nom d'hôte, sans identifiants intégrés. */
class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = is_string($value) ? trim($value) : '';
        $parts = parse_url($url);

        if ($parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || filter_var($url, FILTER_VALIDATE_URL) === false) {
            $fail('Le lien doit être une adresse complète commençant par https:// (par exemple un lien de partage Google Drive).');
        }
    }
}
