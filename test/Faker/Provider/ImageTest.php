<?php

namespace Faker\Test\Provider;

use Faker\Provider\Image;
use Faker\Test\TestCase;

/**
 * @group legacy
 */
final class ImageTest extends TestCase
{
    public function testImageUrlUses640x680AsTheDefaultSize(): void
    {
        self::assertMatchesRegularExpression(
            '#^https://picsum.photos/640/480#',
            Image::imageUrl(),
        );
    }

    public function testImageUrlAcceptsCustomWidthAndHeight(): void
    {
        self::assertMatchesRegularExpression(
            '#^https://picsum.photos/800/400#',
            Image::imageUrl(800, 400),
        );
    }

    public function testImageUrlReturnsLinkToRegularImageWhenGrayIsFalse(): void
    {
        $imageUrl = Image::imageUrl(800, 400);

        self::assertMatchesRegularExpression(
            '#^https://picsum.photos/800/400#',
            $imageUrl,
        );
    }

    public function testImageUrlReturnsLinkToRegularImageWhenGrayIsTrue(): void
    {
        $imageUrl = Image::imageUrl(800, 400, true, true);

        self::assertMatchesRegularExpression(
            '#^https://picsum.photos/800/400\?grayscale#',
            $imageUrl,
        );

    }


    public function testImageUrlAcceptsDifferentImageFormats(): void
    {
        foreach (Image::getFormats() as $format) {
            $imageUrl = Image::imageUrl(
                800,
                400,
                false,
                false,
                false,
                null,
                null,
                $format
            );

            self::assertMatchesRegularExpression(
                "#^https://picsum.photos/800/400.{$format}#",
                $imageUrl,
            );
        }
    }

    public function testDownloadWithDefaults(): void
    {
        self::checkUrlConnection('https://picsum.photos');


        $file = Image::image(sys_get_temp_dir());
        self::assertFileExists($file);

        self::checkImageProperties($file, 640, 480, 'jpg');
    }

    public function testDownloadWithDifferentImageFormats(): void
    {
        self::checkUrlConnection('https://picsum.photos');

        $formats = ['jpg', 'webp'];
        foreach ($formats as $format) {
            $width = 800;
            $height = 400;
            $file = Image::image(
                sys_get_temp_dir(),
                $width,
                $height,
                true,
                false,
                false,
                $format
            );
          
            self::assertFileExists($file);

            self::checkImageProperties($file, $width, $height, $format);
        }
    }

    private static function checkImageProperties(
        string $file,
        int $width,
        int $height,
        string $format
    ): void {
        if (function_exists('getimagesize')) {
            $imageConstants = Image::getFormatConstants();
            [$actualWidth, $actualHeight, $type, $attr] = getimagesize($file);
            self::assertEquals($width, $actualWidth);
            self::assertEquals($height, $actualHeight);
            self::assertEquals($imageConstants[$format], $type);
        } else {
            self::assertEquals($format, pathinfo($file, PATHINFO_EXTENSION));
        }

        if (file_exists($file)) {
            unlink($file);
        }
    }

    private static function checkUrlConnection(string $url): void
    {
        $curlPing = curl_init($url);
        curl_setopt($curlPing, CURLOPT_TIMEOUT, 5);
        curl_setopt($curlPing, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curlPing, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlPing, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curlPing);
        $httpCode = curl_getinfo($curlPing, CURLINFO_HTTP_CODE);
        curl_close($curlPing);

        if ($httpCode < 200 || $httpCode > 300) {
            self::markTestSkipped(sprintf('"%s" is offline, skipping test', $url));
        }
    }
}
