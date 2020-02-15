<?php
declare(strict_types=1);

namespace App\Api\Entity;

/**
 * @package App\Api\Entity
 */
interface TokenInterface
{
    public const FIELD_TOKEN = 'token';
    public const FIELD_SECRET = 'secret';
}