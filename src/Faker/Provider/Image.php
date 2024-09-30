<?php

namespace Faker\Provider;

/**
 * Depends on image generation from http://lorempixel.com/
 */
class Image extends Base
{
    /**
     * @var string
     */
    protected static $baseUrl = 'https://picsum.photos';

    public const FORMAT_JPG = 'jpg';
    public const FORMAT_WEBP = 'webp';

     /**
     * Set the base URL for image generation
     *
     * @param string $url
     */
    public static function setBaseUrl($url)
    {
        self::$baseUrl = rtrim($url, '/');
    }

    /**
     * @var array
     *
     * @deprecated Categories are no longer used as a list in the placeholder API but referenced as string instead
     */
    protected static $categories = [
        'abstract', 'animals', 'business', 'cats', 'city', 'food', 'nightlife',
        'fashion', 'people', 'nature', 'sports', 'technics', 'transport',
    ];

    /**
     * Generate the URL that will return a random image from Lorem Picsum
     *
     * @param int $width
     * @param int $height
     * @param bool $grayscale
     * @param bool $blur
     * @param string|null $specificImage
     * @param bool $randomize
     * @param string|null $seed
     * @param string|null $format Image format (e.g., 'jpg', 'webp')
     *
     * @return string
     */
    public static function imageUrl(
        $width = 640,
        $height = 480,
        $randomize = true,
        $grayscale = false,      
        $blur = false,
        $specificImage = null,
        $seed = null,
        $format = null
    ) {
        $url = self::$baseUrl;

        // If a specific image is requested, add it to the URL
        if ($specificImage !== null) {
            $url .= "/id/{$specificImage}";
        }

        // Add dimensions and format
        $url .= "/{$width}/{$height}";
        if ($format !== null) {
            $url .= ".{$format}";
        }

        $params = [];

        // Add grayscale if requested
        if ($grayscale) {
            $params[] = 'grayscale';
        }

        // Add blur if requested (value between 1-10)
        if ($blur) {
            $blurAmount = mt_rand(1, 10);
            $params[] = "blur={$blurAmount}";
        }

        // Add seed if provided
        if ($seed !== null) {
            $params[] = "seed={$seed}";
        }

        // Add randomization if requested
        if ($randomize && $seed === null) {
            $params[] = 'random=' . mt_rand(1, 1000);
        }

        // Append parameters to URL if any
        if (!empty($params)) {
            $url .= '?' . implode('&', $params);
        }

        return $url;
  
    }

    /**
     * Download a remote random image to disk and return its location
     *
     * Requires curl, or allow_url_fopen to be on in php.ini.
     *
     * @example '/path/to/dir/13b73edae8443990be1aa8f1a483bc27.jpg'
     *
     * @return bool|string
     */
    public static function image(
        $dir = null,
        $width = 640,
        $height = 480,
        $fullPath = true,
        $randomize = true,
        $grayscale = false,      
        $format = 'jpg'
    ) {
        $dir = null === $dir ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible

        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = sprintf('%s.%s', $name, $format);
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::imageUrl($width, $height, $randomize, $grayscale, false, null, null, $format);

        // save file
        if (function_exists('curl_exec')) {

            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($filepath);

                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            $success = copy($url, $filepath);

            if (!$success) {
                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }

    public static function getFormats(): array
    {
        return array_keys(static::getFormatConstants());
    }

    public static function getFormatConstants(): array
    {
        return [
            static::FORMAT_JPG => constant('IMAGETYPE_JPEG'),
            static::FORMAT_WEBP => constant('IMAGETYPE_WEBP'),
        ];
    }
}
