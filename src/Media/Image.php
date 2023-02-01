<?php

namespace App\Media;

use GdImage;

class Image
{
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
        $contents = imagecreatefromstring($contents);
        if ($contents === false) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $scaledImage = self::scaleImage($contents, $width, $height);

        $successfulColorConversion = imagepalettetotruecolor($scaledImage);

        if (!$successfulColorConversion) {
            throw new ImageProcessingException(ImageProcessingException::COLOR_PALETTE_CONVERSION);
        }

        return self::convertToWebp($scaledImage);
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
    private static function scaleImage(GdImage|false $contents, int $width, int $height): GdImage
    {
        $destinationWidth = self::IMAGE_WIDTH;
        $destinationHeight = intval(floor(self::IMAGE_WIDTH * $height / $width));

        $destinationImage = imagecreatetruecolor($destinationWidth, $destinationHeight);

        if (!($destinationImage instanceof GdImage)) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $scaledImage = imagescale(
            $contents,
            $destinationWidth,
            $destinationHeight
        );

        if (!$scaledImage) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_SCALING);
        }

        return $scaledImage;
    }

    /**
     * @throws \App\Media\ImageProcessingException
     */
    public static function convertToWebp(GdImage $scaledImage): string
    {
        ob_start();
        $isSuccessfulConversion = imagewebp($scaledImage);

        if ($isSuccessfulConversion === false) {
            throw new ImageProcessingException(ImageProcessingException::IMAGE_CREATION);
        }

        $webpImageContents = ob_get_contents();
        ob_end_clean();

        return $webpImageContents;
    }
}