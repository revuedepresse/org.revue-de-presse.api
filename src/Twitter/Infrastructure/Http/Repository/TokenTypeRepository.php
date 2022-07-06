<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Repository;

use App\Twitter\Domain\Http\Repository\TokenTypeRepositoryInterface;
use App\Twitter\Infrastructure\Http\Entity\TokenType;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;

/**
 * @method TokenType|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenType|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenType[]    findAll()
 * @method TokenType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenTypeRepository extends ServiceEntityRepository implements TokenTypeRepositoryInterface
{
    use LoggerTrait;

    public function ensureTokenTypesExist(): void
    {
        $entityManager = $this->getEntityManager();

        try {
            $applicationTokenTypes = $this->findBy(['name' => TokenType::APPLICATION]);
            if (empty($applicationTokenTypes)) {
                $applicationTokenType = self::applicationTokenType();
                $entityManager->persist($applicationTokenType);
            }

            $userTokenTypes = $this->findBy(['name' => TokenType::USER]);
            if (empty($userTokenTypes)) {
                $userTokenType = self::userTokenType();
                $entityManager->persist($userTokenType);
            }

            $entityManager->flush();
        } catch (ORMException $exception) {
              $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public static function applicationTokenType(): TokenType
    {
        return new TokenType(TokenType::APPLICATION);
    }

    public static function userTokenType(): TokenType
    {
        return new TokenType(TokenType::USER);
    }
}
