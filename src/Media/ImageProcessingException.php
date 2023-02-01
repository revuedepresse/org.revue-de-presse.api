<?php

namespace App\Media;

class ImageProcessingException extends \Exception
{
    public const IMAGE_CREATION = 'Cannot create destination image.';
    public const IMAGE_SCALING = 'Cannot scale image.';
    public const COLOR_PALETTE_CONVERSION = 'Cannot convert color palette.';
}