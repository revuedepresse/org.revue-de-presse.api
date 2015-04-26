<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints;

Use Symfony\Component\Translation\Translator,
    Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class QueryValidator
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Validator\Contraints
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class QueryValidator extends ConstraintValidator
{
    /**
     * @var \Symfony\Component\Translation\LoggingTranslator
     */
    protected $translator;

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint)
    {
        /**
         * @var Query $constraint
         */
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

        if ($this->context->getGroup() === $constraint::GROUP_PUBLIC_QUERIES) {
            $checklist[] = ['method' => 'updateData'];
        }

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
     * @return bool
     */
    public function updateData($sql)
    {
        $validQueryCount = $this->validQueryCount($sql, 'update', 'update tmp_');

        return $this->assertContains('update', $sql) && !$validQueryCount;
    }

    /**
     * @param $needle
     * @param $haystack
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
