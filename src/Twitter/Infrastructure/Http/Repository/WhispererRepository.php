<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Repository;

use App\Membership\Domain\Repository\WhispererRepositoryInterface;
use App\Twitter\Infrastructure\Http\Entity\Whisperer;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;

/**
 * @method Whisperer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Whisperer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Whisperer[]    findAll()
 * @method Whisperer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WhispererRepository extends ResourceRepository implements WhispererRepositoryInterface
{
    /**
     * @param Whisperer $whisperer
     *
     * @return Whisperer
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareWhisperer(Whisperer $whisperer): Whisperer
    {
        $preExistingWhisperer = $this->findOneBy(['name' => $whisperer->getName()]);

        if ($preExistingWhisperer instanceof Whisperer) {
            $preExistingWhisperer->setExpectedWhispers($whisperer->getExpectedWhispers());
            $whisperer = $this->rotateWhispers($preExistingWhisperer, $whisperer->getWhispers());
        }

        return $this->saveWhisperer($whisperer);
    }

    /**
     * @param Whisperer $whisperer
     * @param int $whispers
     * @return Whisperer
     */
    protected function rotateWhispers(Whisperer $whisperer, $whispers)
    {
        $whisperer->setPreviousWhispers($whisperer->getWhispers());

        return $whisperer->setWhispers($whispers);
    }

    /**
     * @param Whisperer $whisperer
     * @return Whisperer
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function saveWhisperer(Whisperer $whisperer): Whisperer
    {
        $whisperer->setUpdatedAt(new \DateTime());

        $this->getEntityManager()->persist($whisperer);
        $this->getEntityManager()->flush();

        return $whisperer;
    }

    /**
     * @param $whisperer
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function forgetAboutWhisperer(Whisperer $whisperer): void
    {
        $this->getEntityManager()->remove($whisperer);
        $this->getEntityManager()->flush();
    }
}
