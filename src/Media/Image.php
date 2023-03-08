<?php

namespace App\Media;

use GdImage;

class Image
{
    private const PROFILE_PICTURE_WIDTH = 48;

    private const IMAGE_WIDTH = 570;

    /**
     * @throws \App\Media\ImageProcessingException
     */
    public static function fromJpegToResizedWebp(
        string $contents,
        int $width,
        int $height
    ): string|false
    {
        list($sourceWidth, $sourceHeight) = getimagesizefromstring($contents);
        $contents = imagecreatefromstring($contents);
        if ($contents === false) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $scaledImage = self::scaleImage(
            $contents,
            $sourceWidth,
            $sourceHeight,
            $width,
            $height
        );

        $successfulColorConversion = imagepalettetotruecolor($scaledImage);

        if (!$successfulColorConversion) {
            throw new ImageProcessingException(ImageProcessingException::COLOR_PALETTE_CONVERSION);
        }

        return self::convertToWebp($scaledImage);
    }

    /**
     * @throws \App\Media\ImageProcessingException
     */
    public static function fromJpegProfilePictureToResizedWebp(string $contents, $destinationScaleFactor = 1): string|false
    {
        list($sourceWidth, $sourceHeight) = getimagesizefromstring($contents);
        $contents = imagecreatefromstring($contents);

        if ($contents === false) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $scaledImage = self::scaleProfilePicture(
            $contents,
            $sourceWidth,
            $sourceHeight,
            $destinationScaleFactor
        );

        $successfulColorConversion = imagepalettetotruecolor($scaledImage);

        if (!$successfulColorConversion) {
            throw new ImageProcessingException(ImageProcessingException::COLOR_PALETTE_CONVERSION);
        }

        return self::convertToWebp($scaledImage, 100);
    }

    /**
     * @throws \App\Media\ImageProcessingException
     */
    public static function fromJpegToWebp(string $contents): string
    {
        $contents = imagecreatefromstring($contents);
        if ($contents === false) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $successfulColorConversion = imagepalettetotruecolor($contents);

        if (!$successfulColorConversion) {
            throw new ImageProcessingException(ImageProcessingException::COLOR_PALETTE_CONVERSION);
        }

        return self::convertToWebp($contents);
    }

    /**
     * @throws \App\Media\ImageProcessingException
     */
    private static function scaleProfilePicture(
        GdImage|false $contents,
        int $sourceWidth,
        int $sourceHeight,
        int $destinationScaleFactor = 1
    ): GdImage
    {
        return self::scaleMedia(
            self::PROFILE_PICTURE_WIDTH * $destinationScaleFactor,
            $sourceWidth,
            $sourceHeight,
            self::PROFILE_PICTURE_WIDTH,
            self::PROFILE_PICTURE_WIDTH,
            $contents,
            $destinationScaleFactor
        );
    }

    /**
     * @throws \App\Media\ImageProcessingException
     */
    private static function scaleImage(
        GdImage|false $contents,
        int $sourceWidth,
        int $sourceHeight,
        int $width,
        int $height
    ): GdImage {
        return self::scaleMedia(
            self::IMAGE_WIDTH,
            $sourceWidth,
            $sourceHeight,
            $width,
            $height,
            $contents
        );
    }

    /**
     * @throws \App\Media\ImageProcessingException
     */
    public static function convertToWebp(GdImage $scaledImage, $quality = 80): string
    {
        ob_start();
        $isSuccessfulConversion = imagewebp($scaledImage, quality: $quality);

        if ($isSuccessfulConversion === false) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $webpImageContents = ob_get_contents();
        ob_end_clean();

        return $webpImageContents;
    }

    public static function scaleImageHeight(int $width, int $height, int $targetWidth = self::IMAGE_WIDTH): int
    {
        return intval(floor($targetWidth * $height / $width));
    }

    /**
     * @throws \App\Media\ImageProcessingException
     */
    public static function scaleMedia(
        int $targetWidth,
        int $sourceWidth,
        int $sourceHeight,
        int $width,
        int $height,
        GdImage|false $contents,
        int $destinationScaleFactor = 1
    ): GdImage|resource {
        $destinationWidth = $targetWidth;
        $destinationHeight = self::scaleImageHeight($width, $height, $targetWidth);

        $destinationImage = imagecreatetruecolor($destinationWidth, $destinationHeight);

        if (!($destinationImage instanceof GdImage)) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $result = imagecopyresampled(
            $destinationImage,
            $contents,
            0,
            0,
            0,
            0,
            $destinationWidth,
            $destinationHeight,
            $sourceWidth,
            $sourceHeight
        );

        if (!$result) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_SCALING);
        }

        return $destinationImage;
    }
}