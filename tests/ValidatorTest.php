<?php

declare(strict_types=1);

namespace MiBo\Locales\Tests;

use MiBo\Locales\Exceptions\MissingSubTagException;
use MiBo\Locales\Exceptions\NotNormalLangTagException;
use MiBo\Locales\LocaleTag;
use MiBo\Locales\Validators\TagValidator;
use PHPUnit\Framework\TestCase;

/**
 * Class ValidatorTest
 *
 * @package MiBo\Locales\Tests
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 *
 * @coversDefaultClass \MiBo\Locales\LocaleTag
 */
final class ValidatorTest extends TestCase
{
    /**
     * @small
     *
     * @covers ::__construct
     * @covers ::getLanguage
     * @covers ::getExtlang
     * @covers ::getScript
     * @covers ::getRegion
     * @covers ::getVariants
     * @covers ::getExtension
     * @covers ::getExtensions
     * @covers ::getPrivateuse
     * @covers ::isPrivateUse
     * @covers ::isGrandfatheredIrregular
     * @covers ::isGrandfatheredRegular
     * @covers ::isGrandfathered
     * @covers ::isLangTag
     * @covers ::purify
     * @covers ::format
     * @covers ::getTag
     * @covers ::useDash
     * @covers ::useUnderscore
     * @covers \MiBo\Locales\Exceptions\NotNormalLangTagException::__construct
     * @covers \MiBo\Locales\Exceptions\MissingSubTagException::__construct
     * @covers \MiBo\Locales\Validators\TagValidator::validate
     *
     * @param string $tag
     * @param array<string, string|array<string>> $data
     *
     * @return void
     *
     * @dataProvider getValidData
     */
    public function testValidator(string $tag, array $data): void
    {
        self::assertTrue((new TagValidator())->validate($tag));

        $locale = new LocaleTag($tag);

        $keys = [
            'language' => 'getLanguage',
            'extlang' => 'getExtlang',
            'script' => 'getScript',
            'region' => 'getRegion',
            'variant' => 'getVariants',
            'extensions' => 'getExtension',
            'privateuse' => 'getPrivateuse',
        ];

        foreach ($keys as $key => $method) {
            if ($key === 'extensions') {
                $method = 'getExtension';

                if (key_exists($key, $data)) {
                    foreach ($data[$key] as $k => $v) {
                        self::assertSame($v, $locale->$method($k));
                    }

                    $locale->getExtensions();
                } else {
                    try {
                        $locale->$method('asd');
                        self::fail('Exception should be thrown.');
                    } catch (MissingSubTagException|NotNormalLangTagException) {
                        self::assertTrue(true);
                    }

                    try {
                        $locale->getExtensions();
                    } catch (MissingSubTagException|NotNormalLangTagException) {
                    }
                }

                continue;
            }

            if (key_exists($key, $data)) {
                self::assertSame($data[$key], $locale->$method());
            } else {
                try {
                    $locale->$method();
                    self::fail('Exception should be thrown.' . $method . $locale->getLanguage() .
                               $locale->$method());
                } catch (MissingSubTagException|NotNormalLangTagException) {
                    self::assertTrue(true);
                }
            }
        }

        if (empty($data)) {
            $isNonStandard = $locale->isPrivateUse();
            $isNonStandard = $locale->isGrandfatheredIrregular() || $isNonStandard;
            $isNonStandard = $locale->isGrandfatheredRegular() || $isNonStandard;

            $this->assertTrue($isNonStandard);
        }

        $this->assertSame($tag, $locale->getTag());

        self::assertSame(str_replace('_', '-', $tag), $locale->useDash()->getTag());
        self::assertSame($tag, $locale->useUnderscore()->getTag());
    }

    /**
     * @small
     *
     * @covers \MiBo\Locales\Validators\TagValidator::validate
     * @covers \MiBo\Locales\Exceptions\InvalidLocaleFormatException::__construct
     * @covers ::__construct
     *
     * @param string $tag
     *
     * @return void
     *
     * @dataProvider getInvalidTags
     */
    public function testValidator2(string $tag): void
    {
        self::assertFalse((new TagValidator())->validate($tag));
    }

    /**
     * @return array<array<string|array<string, string>>>
     */
    public static function getValidData(): array
    {
        return [
            [
                'cs',
                ['language' => 'cs'],
            ],
            [
                'cs_CZ',
                [
                    'language' => 'cs',
                    'region' => 'CZ',
                ],
            ],
            [
                'de',
                ['language' => 'de'],
            ],
            [
                'fr',
                ['language' => 'fr'],
            ],
            [
                'ja',
                ['language' => 'ja'],
            ],
            [
                'i_enochian',
                [],
            ],
            [
                'zh_Hant',
                [
                    'language' => 'zh',
                    'script' => 'Hant',
                ],
            ],
            [
                'zh_Hans',
                [
                    'language' => 'zh',
                    'script' => 'Hans',
                ],
            ],
            [
                'sr_Cyrl',
                [
                    'language' => 'sr',
                    'script' => 'Cyrl',
                ],
            ],
            [
                'sr_Latn',
                [
                    'language' => 'sr',
                    'script' => 'Latn',
                ],
            ],
            [
                'zh_cmn_Hans_CN',
                [
                    'language' => 'zh_cmn',
                    'extlang' => 'cmn',
                    'script' => 'Hans',
                    'region' => 'CN',
                ],
            ],
            [
                'cmn_Hans_CN',
                [
                    'language' => 'cmn',
                    'script' => 'Hans',
                    'region' => 'CN',
                ],
            ],
            [
                'zh_yue_HK',
                [
                    'language' => 'zh_yue',
                    'extlang' => 'yue',
                    'region' => 'HK',
                ],
            ],
            [
                'yue_HK',
                [
                    'language' => 'yue',
                    'region' => 'HK',
                ],
            ],
            [
                'zh_Hans_CN',
                [
                    'language' => 'zh',
                    'script' => 'Hans',
                    'region' => 'CN',
                ],
            ],
            [
                'sr_Latn_RS',
                [
                    'language' => 'sr',
                    'script' => 'Latn',
                    'region' => 'RS',
                ],
            ],
            [
                'sl_rozaj',
                [
                    'language' => 'sl',
                    'variant' => [1 => 'rozaj'],
                ],
            ],
            [
                'sl_rozaj_biske',
                [
                    'language' => 'sl',
                    'variant' => [
                        1 => 'rozaj',
                        2 => 'biske',
                    ],
                ],
            ],
            [
                'sl_nedis',
                [
                    'language' => 'sl',
                    'variant' => [1 => 'nedis'],
                ],
            ],
            [
                'de_CH_1901',
                [
                    'language' => 'de',
                    'region' => 'CH',
                    'variant' => [1 => '1901'],
                ],
            ],
            [
                'sl_IT_nedis',
                [
                    'language' => 'sl',
                    'region' => 'IT',
                    'variant' => [1 => 'nedis'],
                ],
            ],
            [
                'hy_Latn_IT_arevela',
                [
                    'language' => 'hy',
                    'script' => 'Latn',
                    'region' => 'IT',
                    'variant' => [1 => 'arevela'],
                ],
            ],
            [
                'de_DE',
                [
                    'language' => 'de',
                    'region' => 'DE',
                ],
            ],
            [
                'en_US',
                [
                    'language' => 'en',
                    'region' => 'US',
                ],
            ],
            [
                'es_419',
                [
                    'language' => 'es',
                    'region' => '419',
                ],
            ],
            [
                'de_CH_x_phonebk',
                [
                    'language' => 'de',
                    'region' => 'CH',
                    'privateuse' => 'x_phonebk',
                ],
            ],
            [
                'az_Arab_x_aze_derbend',
                [
                    'language' => 'az',
                    'script' => 'Arab',
                    'privateuse' => 'x_aze_derbend',
                ],
            ],
            [
                'x_whatever',
                [
                    'privateuse' => 'x_whatever',
                ],
            ],
            [
                'qaa_Qaaa_QM_x_southern',
                [
                    'language' => 'qaa',
                    'script' => 'Qaaa',
                    'region' => 'QM',
                    'privateuse' => 'x_southern',
                ],
            ],
            [
                'de_Qaaa',
                [
                    'language' => 'de',
                    'script' => 'Qaaa',
                ],
            ],
            [
                'sr_Latn_QM',
                [
                    'language' => 'sr',
                    'script' => 'Latn',
                    'region' => 'QM',
                ],
            ],
            [
                'sr_Qaaa_RS',
                [
                    'language' => 'sr',
                    'script' => 'Qaaa',
                    'region' => 'RS',
                ],
            ],
            [
                'en_US_u_islamcal',
                [
                    'language' => 'en',
                    'region' => 'US',
                    'extensions' => ['u' => 'u_islamcal'],
                ],
            ],
            [
                'zh_CN_a_myext_x_private',
                [
                    'language' => 'zh',
                    'region' => 'CN',
                    'extensions' => ['a' => 'a_myext'],
                    'privateuse' => 'x_private',
                ],
            ],
            [
                'en_a_myext_b_another',
                [
                    'language' => 'en',
                    'extensions' => [
                        'a' => 'a_myext',
                        'b' => 'b_another',
                    ],
                ],
            ],
            [
                'art_lojban',
                [],
            ],
        ];
    }

    /**
     * @return array<array<non-empty-string>>
     */
    public static function getInvalidTags(): array
    {
        return [
            ['de-419-DE'],
            ['a-DE'],
            ['ar-a-aaa-b-bbb-a-ccc'],
        ];
    }
}
