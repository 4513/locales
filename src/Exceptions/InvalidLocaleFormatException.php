<?php

declare(strict_types=1);

namespace MiBo\Locales\Exceptions;

use InvalidArgumentException;
use MiBo\Locales\Contracts\LocaleExceptionInterface;

/**
 * Class InvalidLocaleFormatException
 *
 * @package MiBo\Locales\Exceptions
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class InvalidLocaleFormatException extends InvalidArgumentException implements LocaleExceptionInterface
{
    public function __construct(string $value)
    {
        parent::__construct(
            'Invalid locale format: ' . $value . '. Check https://datatracker.ietf.org/doc/html/rfc5646#section-2.1.'
        );
    }
}
