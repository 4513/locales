<?php

declare(strict_types=1);

namespace MiBo\Locales;

use JetBrains\PhpStorm\Pure;
use MiBo\Locales\Exceptions\InvalidLocaleFormatException;
use MiBo\Locales\Exceptions\MissingSubTagException;
use MiBo\Locales\Exceptions\NotNormalLangTagException;
use function in_array;

/**
 * Class Locale
 *
 * @package MiBo\Locales
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @link https://datatracker.ietf.org/doc/html/rfc5646
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class LocaleTag
{
    private const GRANDFATHERED                = [
        'irregular' => [
            'en_GB_oed',
            'i_ami',
            'i_bnn',
            'i_default',
            'i_enochian',
            'i_hak',
            'i_klingon',
            'i_lux',
            'i_mingo',
            'i_navajo',
            'i_pwn',
            'i_tao',
            'i_tay',
            'i_tsu',
            'sgn_BE_FR',
            'sgn_BE_NL',
            'sgn-CH_DE',
        ],
        'regular' => [
            'art_lojban',
            'cel_gaulish',
            'no_bok',
            'no_nyn',
            'zh_guoyu',
            'zh_hakka',
            'zh_min',
            'zh_min_nan',
            'zh_xiang',
        ],
    ];
    private const REGEX_LANGTAG                = '/^((?P<ISO639>[a-zA-Z]{2,3}(?P<EXTLANG>(((\_[a-zA-Z]{3})|(\_[a-zA-Z]{3}){,2}))?))|(?P<LANG4>[a-zA-Z]{4})|(?P<LANG5>[a-zA-Z]{5,8}))(?P<ISO15924>(\_[a-zA-Z]{4})?)((?P<ISO3166A2>(\_[a-zA-Z]{2})?)|(?P<UNM49>(\_[0-9]{3})?))(?P<VARIANT>((\_[a-zA-Z0-9]{5,8})|(\_\d[a-zA-Z0-9]{3}))*)(?P<EXTENSION>(\_([0-9A-WY-Za-wy-z](\_[a-zA-Z0-9]{2,8})+))*)(?P<PRIVATE>((\_x(\_[a-zA-Z0-9]{1,8})+))?)$/';

    private const REGEX_PRIVATEUSE             = '/^x(?P<PRIVATE>(\_[a-zA-Z\d]{1,8}))+$/';
    private const SEPARATOR_DASH               = '-';
    private const SEPARATOR_UNDERSCORE         = '_';
    private const TYPE_LANGTAG                 = 'langtag';
    private const TYPE_PRIVATEUSE              = 'privateuse';
    private const TYPE_GRANDFATHERED_REGULAR   = 'grandfathered-r';
    private const TYPE_GRANDFATHERED_IRREGULAR = 'grandfathered-i';

    private string $type;

    private string $tag;

    private bool $usesUnderscore;

    /**
     * @var array{
     *     language: non-empty-string|null,
     *     region: non-empty-string|null,
     *     script: non-empty-string|null,
     *     variants: array<non-empty-string>,
     *     extensions: array<non-empty-string, non-empty-string>,
     *     privateUse: array<non-empty-string>,
     * }
     */
    private array $subTags = [
        'extensions' => [],
        'language'   => null,
        'language.extlang' => null,
        'privateUse' => [],
        'region' => null,
        'script' => null,
        'variants' => [],
    ];

    /**
     * @param non-empty-string $tag
     */
    public function __construct(string $tag)
    {
        $this->usesUnderscore = str_contains($tag, self::SEPARATOR_UNDERSCORE);

        $tag = str_replace('-', '_', $tag);

        // Checks that the tag is Grandfathered tag. If so, it may be either regular or irregular.
        if (in_array($tag, self::GRANDFATHERED['irregular'], true)) {
            $this->type = self::TYPE_GRANDFATHERED_IRREGULAR;
            $this->tag  = $tag;

            return;
        }

        if (in_array($tag, self::GRANDFATHERED['regular'], true)) {
            $this->type = self::TYPE_GRANDFATHERED_REGULAR;
            $this->tag  = $tag;

            return;
        }

        // Checks that the tag is privately used tag.
        if (preg_match(self::REGEX_PRIVATEUSE, $tag, $matches) === 1) {
            $this->type = self::TYPE_PRIVATEUSE;
            $this->tag  = $tag;
            $this->subTags['privateUse'] = array_filter(
                explode('_', $matches['PRIVATE']),
                static fn (string $subTag): bool => $subTag !== ''
            );

            return;
        }

        $isLangTag = preg_match(self::REGEX_LANGTAG, $tag, $matches);

        if ($isLangTag !== 1) {
            throw new InvalidLocaleFormatException($tag);
        }

        $this->subTags['language']         = $matches['ISO639'] !== ''
            ? $matches['ISO639']
            : $matches['LANG4'];
        $this->subTags['language']         = $this->subTags['language'] === ''
            ? $matches['LANG5']
            : $this->subTags['language'];
        $this->subTags['language.extlang'] = trim($matches['EXTLANG'] ?? '', '_');
        $this->subTags['language.extlang'] = $this->subTags['language.extlang'] === ''
            ? null
            : $this->subTags['language.extlang'];
        $this->subTags['script']           = trim($matches['ISO15924'] ?? '', '_');
        $this->subTags['script']           = $this->subTags['script'] === ''
            ? null
            : $this->subTags['script'];
        $this->subTags['region']           = trim(
            ($matches['ISO3166A2'] !== '' ? $matches['ISO3166A2'] : $matches['UNM49']),
            '_'
        );
        $this->subTags['region']           = $this->subTags['region'] === ''
            ? null
            : $this->subTags['region'];
        $this->subTags['variants']         = array_filter(
            explode('_', $matches['VARIANT']),
            static fn (string $subTag): bool => $subTag !== ''
        );

        $extensions =  array_filter(
            explode('_', $matches['EXTENSION'] ?? ''),
            static fn (string $subTag): bool => $subTag !== ''
        );
        $currentExtension = null;

        foreach ($extensions as $extension) {
            $currentExtension ??= $extension;

            if (strlen($extension) === 1) {
                $currentExtension = $extension;

                if (key_exists($currentExtension, $this->subTags['extensions'])) {
                    throw new InvalidLocaleFormatException($tag);
                }

                $this->subTags['extensions'][$currentExtension] = '';

                continue;
            }

            $this->subTags['extensions'][$currentExtension] .= '_' . $extension;
        }

        $this->subTags['privateUse'] = array_filter(
            explode('_', $matches['PRIVATE']),
            static fn (string $subTag): bool => $subTag !== '' && $subTag !== 'x'
        );

        $this->type = self::TYPE_LANGTAG;
        $this->tag  = $tag;

        $this->purify();
    }

    /**
     * @return bool True if the tag is Grandfathered, false otherwise.
     */
    #[Pure]
    public function isGrandfathered(): bool
    {
        return $this->isGrandfatheredIrregular() || $this->isGrandfatheredRegular();
    }

    /**
     * @return bool True if the tag is Grandfathered regular, false otherwise.
     */
    #[Pure]
    public function isGrandfatheredRegular(): bool
    {
        return $this->type === self::TYPE_GRANDFATHERED_REGULAR;
    }

    /**
     * @return bool True if the tag is Grandfathered irregular, false otherwise.
     */
    #[Pure]
    public function isGrandfatheredIrregular(): bool
    {
        return $this->type === self::TYPE_GRANDFATHERED_IRREGULAR;
    }

    /**
     * @return bool True if the tag is private use, false otherwise.
     */
    #[Pure]
    public function isPrivateUse(): bool
    {
        return $this->type === self::TYPE_PRIVATEUSE;
    }

    /**
     * @return bool True if the tag is langtag, false otherwise.
     */
    #[Pure]
    public function isLangTag(): bool
    {
        return $this->type === self::TYPE_LANGTAG;
    }

    /**
     * Convert the tag to use underscore instead of dash.
     *
     * @return static
     */
    public function useUnderscore(): self
    {
        $this->usesUnderscore = true;

        $this->purify();

        return $this;
    }

    /**
     * Convert the tag to use dash instead of underscore.
     *
     * @return static
     */
    public function useDash(): self
    {
        $this->usesUnderscore = false;

        $this->purify();

        return $this;
    }

    /**
     * Returns a language subtag from the tag.
     *
     * The language is one of:
     * * ISO 639 Alpha 2 code, or;
     * * ISO 639 Alpha 3 code, or;
     * * ISO 639 Alpha 2 code with extended lang separated by separator, or;
     * * ISO 639 Alpha 3 code with extended lang separated by separator, or;
     * * Alpha 4 for reserved future use, or;
     * * Alpha 5–8 for registered language.
     *
     * @return non-empty-string Language subtag.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     */
    public function getLanguage(): string
    {
        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            throw new NotNormalLangTagException('language');
        }

        /** @phpstan-var non-empty-string */
        return $this->subTags['language'];
    }

    /**
     * Returns an extended language subtag.
     *
     * Selected ISO 639 codes (Alpha 3) with none or up to two 3 Alpha codes separated by separator.
     *
     * @return non-empty-string Extended language subtag.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     * @throws \MiBo\Locales\Exceptions\MissingSubTagException
     */
    public function getExtLang(): string
    {
        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            throw new NotNormalLangTagException('extlang');
        }

        if ($this->subTags['language.extlang'] === null) {
            throw new MissingSubTagException('extlang', $this->tag);
        }

        return $this->subTags['language.extlang'];
    }

    /**
     * Returns a script subtag.
     *
     * ISO 15924 code.
     *
     * @return non-empty-string Script subtag.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     * @throws \MiBo\Locales\Exceptions\MissingSubTagException
     */
    public function getScript(): string
    {
        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            throw new NotNormalLangTagException('script');
        }

        if ($this->subTags['script'] === null) {
            throw new MissingSubTagException('script', $this->tag);
        }

        return $this->subTags['script'];
    }

    /**
     * Returns a region subtag.
     *
     * ISO 3166-1 Alpha 2 code or UN M.49 code (3 digits).
     *
     * @return non-empty-string|numeric-string Region subtag.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     * @throws \MiBo\Locales\Exceptions\MissingSubTagException
     */
    public function getRegion(): string
    {
        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            throw new NotNormalLangTagException('region');
        }

        if ($this->subTags['region'] === null) {
            throw new MissingSubTagException('region', $this->tag);
        }

        return $this->subTags['region'];
    }

    /**
     * Returns a list of variant subtags.
     *
     * Registered variants.
     *
     * @return non-empty-array<non-empty-string> Variants subtags.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     * @throws \MiBo\Locales\Exceptions\MissingSubTagException
     */
    public function getVariants(): array
    {
        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            throw new NotNormalLangTagException('variants');
        }

        if (count($this->subTags['variants']) === 0) {
            throw new MissingSubTagException('variants', $this->tag);
        }

        return $this->subTags['variants'];
    }

    /**
     * Returns a list of extension subtags.
     *
     * Registered extensions.
     *
     * @return non-empty-array<non-empty-string> List of extensions subtags.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     * @throws \MiBo\Locales\Exceptions\MissingSubTagException
     */
    public function getExtensions(): array
    {
        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            throw new NotNormalLangTagException('extensions');
        }

        if (count($this->subTags['extensions']) === 0) {
            throw new MissingSubTagException('extensions', $this->tag);
        }

        $list = [];

        foreach (array_keys($this->subTags['extensions']) as $ext) {
            $list[] = $this->getExtension($ext);
        }

        /** @phpstan-var non-empty-array<non-empty-string> */
        return $list;
    }

    /**
     * Returns an extension subtag.
     *
     * @param non-empty-string $extension Extension subtag.
     *
     * @return non-empty-string Extension subtag.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     * @throws \MiBo\Locales\Exceptions\MissingSubTagException
     */
    public function getExtension(string $extension): string
    {
        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            throw new NotNormalLangTagException('extensions');
        }

        if (!key_exists($extension, $this->subTags['extensions'])) {
            throw new MissingSubTagException('extension:' . $extension, $this->tag);
        }

        return $extension . $this->subTags['extensions'][$extension];
    }

    /**
     * Returns a private use subtag.
     *
     * @return non-empty-string Private use subtag.
     *
     * @throws \MiBo\Locales\Exceptions\NotNormalLangTagException
     * @throws \MiBo\Locales\Exceptions\MissingSubTagException
     */
    public function getPrivateUse(): string
    {
        if ($this->isGrandfathered()) {
            throw new NotNormalLangTagException('privateUse');
        }

        if ($this->isLangTag() && count($this->subTags['privateUse']) === 0) {
            throw new MissingSubTagException('privateUse', $this->tag);
        }

        return 'x' . ($this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH) . implode(
            $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH,
            $this->subTags['privateUse']
            );
    }

    /**
     * @return non-empty-string Tag.
     */
    public function getTag(): string
    {
        return $this->format();
    }

    /**
     * @return non-empty-string Tag.
     */
    public function format(): string
    {
        /** @phpstan-var non-empty-string */
        return str_replace('_', $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH, $this->tag);
    }

    private function purify(): void
    {
        $this->tag = strtolower($this->tag);

        if ($this->isGrandfathered() || $this->isPrivateUse()) {
            return;
        }

        // @phpstan-ignore-next-line
        $this->subTags['language'] = strtolower($this->subTags['language']);

        if ($this->subTags['language.extlang'] !== null) {
            $this->subTags['language.extlang'] = strtolower($this->subTags['language.extlang']);
        }

        if ($this->subTags['script'] !== null) {
            $this->subTags['script'] = ucfirst(strtolower($this->subTags['script']));
        }

        if ($this->subTags['region'] !== null) {
            $this->subTags['region'] = strtoupper($this->subTags['region']);
        }

        foreach ($this->subTags['variants'] as $key => $variant) {
            $this->subTags['variants'][$key] = strtolower($variant);
        }

        foreach ($this->subTags['extensions'] as $ext => $value) {
            unset($this->subTags['extensions'][$ext]);
            $this->subTags['extensions'][strtolower($ext)] = str_replace(
                $this->usesUnderscore ? self::SEPARATOR_DASH : self::SEPARATOR_UNDERSCORE,
                $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH,
                strtolower($value)
            );
        }

        foreach ($this->subTags['privateUse'] as $key => $private) {
            // @phpstan-ignore-next-line
            $this->subTags['privateUse'][$key] = str_replace(
                $this->usesUnderscore ? self::SEPARATOR_DASH : self::SEPARATOR_UNDERSCORE,
                $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH,
                strtolower($private)
            );
        }

        $this->tag = implode(
            $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH,
            array_filter(
                [
                    $this->subTags['language'] ?? '',
                    $this->subTags['script'] ?? '',
                    $this->subTags['region'] ?? '',
                    implode(
                        $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH,
                        $this->subTags['variants'] ?? []
                    ),
                ],
                // @phpstan-ignore-next-line
                static fn (?string $subTag): bool => $subTag !== '' && $subTag !== null
            )
        );

        foreach ($this->subTags['extensions'] as $key => $value) {
            $this->tag .= $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH;
            $this->tag .= $key . ($this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH) . $value;
        }

        $private = implode(
            $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH,
            $this->subTags['privateUse'] ?? []
        );

        if (strlen($private) > 0) {
            $this->tag .= $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH;
            $this->tag .= 'x' . ($this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH) . $private;
        }

        $tag = preg_replace(
            '/\\' . (($this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH)) . '{2,}/',
            $this->usesUnderscore ? self::SEPARATOR_UNDERSCORE : self::SEPARATOR_DASH,
            $this->tag
        );

        assert($tag !== null);

        $this->tag = $tag;
    }
}
