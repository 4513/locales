<?php

declare(strict_types=1);

namespace MiBo\Locales\Exceptions;

use LogicException;
use MiBo\Locales\Contracts\LocaleExceptionInterface;

/**
 * Class NotNormalLangTagException
 *
 * @package MiBo\Locales\Exceptions
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since x.x
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class NotNormalLangTagException extends LogicException implements LocaleExceptionInterface
{
    public function __construct(string $key)
    {
        parent::__construct('Grandfathered and private use tags are missing tag ' . $key . '.');
    }
}
