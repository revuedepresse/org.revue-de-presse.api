<?php

namespace WTW\DashboardBundle\Validator\Constraints;

use Symfony\Component\Translation\Translator,
    Symfony\Component\Validator\Constraint;

/**
 * Class Query
 *
 * @package WTW\DashboardBundle\Validator\Contraints
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Query extends Constraint
{
    public $deleteDataViolation = 'constraint_violation.query.delete';

    public $nonEmptyViolation= 'constraint_violation.query.non_empty';

    public $truncateTableViolation= 'constraint_violation.query.truncate';

    public $dropDataViolation= 'constraint_violation.query.drop';

    public $alterSchemaViolation= 'constraint_violation.query.alter';

    public $grantPrivilegeViolation= 'constraint_violation.query.grant';

    public function validatedBy()
    {
        return 'wtw.validator.query';
    }
}
