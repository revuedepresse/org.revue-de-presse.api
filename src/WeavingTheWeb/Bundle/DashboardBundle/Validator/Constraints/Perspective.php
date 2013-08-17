<?php


namespace WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * Class Perspective
 * @package WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Perspective extends Constraint
{
    const GROUP_PUBLIC_PERSPECTIVES = 'public_perspectives';

    public $privacyViolation = 'constraint_violation.perspective.privacy';

    public $queryConstraintViolation = 'constraint_violation.perspective.query_constraint';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'weaving_the_web.validator.perspective';
    }
}