<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints;

use Symfony\Component\Translation\Translator,
    Symfony\Component\Validator\Constraint;

/**
 * Class Query
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Validator\Contraints
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Query extends Constraint
{
    const GROUP_PUBLIC_QUERIES = 'public_queries';

    public $alterSchemaViolation = 'constraint_violation.query.alter';

    public $deleteDataViolation = 'constraint_violation.query.delete';

    public $dropDataViolation = 'constraint_violation.query.drop';

    public $grantPrivilegeViolation = 'constraint_violation.query.grant';

    public $nonEmptyViolation = 'constraint_violation.query.non_empty';

    public $truncateTableViolation = 'constraint_violation.query.truncate';

    public $updateDataViolation = 'constraint_violation.query.update';

    public function validatedBy()
    {
        return 'weaving_the_web.validator.query';
    }
}
