<?php

declare(strict_types=1);

namespace MiBo\Locales\Exceptions;

use MiBo\Locales\Contracts\LocaleExceptionInterface;
use OutOfBoundsException;

/**
 * Class MissingSubTagException
 *
 * @package MiBo\Locales\Exceptions
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class MissingSubTagException extends OutOfBoundsException implements LocaleExceptionInterface
{
    public function __construct(string $key, string $tag)
    {
        parent::__construct(
            strtr(
                'Tag :tag does not have a subtag :key.',
                [
                    ':key' => $key,
                    ':tag' => $tag,
                ]
            )
        );
    }
}
