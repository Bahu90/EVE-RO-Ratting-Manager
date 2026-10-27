<?php

namespace RattingManager\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * A webhook URL, kept encrypted with SeAT's APP_KEY. The token in it lets
 * anyone post to the channel, so it should not sit readable in the
 * database or in a dump of it.
 *
 * A value that is still plain (a row from before the encryption migration)
 * reads as it is. One that can't be decrypted, after APP_KEY changed, reads
 * as an empty string: the webhook then fails its URL check and Settings
 * asks for the URL again, instead of a page crashing.
 */
final class EncryptedUrl implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (str_starts_with((string) $value, 'https://')) {
            return $value;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException $e) {
            return '';
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value === null || $value === '' ? $value : Crypt::encryptString((string) $value);
    }
}
