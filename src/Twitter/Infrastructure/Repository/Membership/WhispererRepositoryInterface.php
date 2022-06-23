<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Repository\Membership;

use App\Twitter\Infrastructure\Api\Entity\Whisperer;

/**
 * @method Whisperer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Whisperer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Whisperer[]    findAll()
 * @method Whisperer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface WhispererRepositoryInterface
{
    public function declareWhisperer(Whisperer $whisperer): Whisperer;
    public function saveWhisperer(Whisperer $whisperer): Whisperer;
    public function forgetAboutWhisperer(Whisperer $whisperer): void;
}