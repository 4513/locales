<?php

declare(strict_types=1);

namespace MiBo\Locales\Validators;

use MiBo\Locales\Exceptions\InvalidLocaleFormatException;
use MiBo\Locales\LocaleTag;

/**
 * Class TagValidator
 *
 * @package MiBo\Locales\Validators
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class TagValidator
{
    public static function validate(string $tag): bool
    {
        try {
            new LocaleTag($tag);

            return true;
        } catch (InvalidLocaleFormatException) {
            return false;
        }
    }
}
