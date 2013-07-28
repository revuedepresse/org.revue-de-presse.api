<?php

namespace WTW\DashboardBundle\Validator\Constraints;

Use Symfony\Component\Translation\Translator,
    Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint;

/**
 * Class QueryValidator
 *
 * @package WTW\DashboardBundle\Validator\Contraints
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class QueryValidator extends ConstraintValidator
{
    /**
     * @var $translator Translator
     */
    protected $translator;

    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint)
    {
        if (strlen(trim($value)) === 0) {
            $this->context->addViolation($this->translator->trans(
                $constraint->nonEmptyViolation, [], 'messages'));
        }

        $checklist  = [
            ['method' => 'deleteData'],
            ['method' => 'truncateTable'],
            ['method' => 'dropData'],
            ['method' => 'alterSchema'],
            ['method' => 'grantPrivilege']
        ];

        foreach ($checklist as $checkpoint) {
            $breakConstraint = $checkpoint['method'];
            if ($this->$breakConstraint($value)) {
                $translationKey = $constraint->{$breakConstraint . 'Violation'};
                $message = $this->translator->trans($translationKey, [], 'messages');
                $this->context->addViolation($message);
            }
        }
    }

    /**
     * @param $sql
     *
     * @return bool
     */
    public function grantPrivilege($sql)
    {
        return $this->assertContains('grant', $sql);
    }

    /**
     * @param $sql
     *
     * @return bool
     */
    public function alterSchema($sql)
    {
        $validQueryCount = $this->validQueryCount($sql, 'alter', 'alter table tmp_');

        return $this->assertContains('alter', $sql) && !$validQueryCount;
    }

    /**
     * @param $sql
     *
     * @return bool
     */
    public function dropData($sql)
    {
        $validQueryCount = $this->validQueryCount($sql, 'drop', 'drop table if exists tmp_');

        return $this->assertContains('drop', $sql) && !$validQueryCount;
    }

    /**
     * @param $sql
     *
     * @return bool
     */
    public function truncateTable($sql)
    {
        $validQueryCount = $this->validQueryCount($sql, 'truncate', 'truncate table tmp_');

        return $this->assertContains('truncate', $sql) && !$validQueryCount;
    }

    /**
     * @param $sql
     *
     * @return bool
     */
    public function assertContains($needle, $haystack)
    {
        return (false !== strpos(strtolower($haystack), $needle));
    }

    /**
     * @param $sql
     *
     * @return bool
     */
    public function deleteData($sql)
    {
        $validQueryCount = $this->validQueryCount($sql, 'delete', 'delete from tmp_');

        return $this->assertContains('delete', $sql) && !$validQueryCount;
    }

    /**
     * @param $sql
     * @param $query
     * @param $exception
     *
     * @return bool
     */
    public function validQueryCount($sql, $query, $exception)
    {
        $loweredSql = strtolower($sql);
        $queries         = substr_count($loweredSql, $query);
        $acceptedQueries = substr_count($loweredSql, $exception);

        return $queries === $acceptedQueries;
    }
}
