<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints;

use Symfony\Component\Translation\TranslatorInterface,
    Symfony\Component\Validator\ConstraintValidatorInterface,
    Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint,
    Symfony\Component\Validator\ExecutionContextInterface,
    Symfony\Component\Validator\ValidatorInterface;

/**
 * Class PerspectiveValidator
 * @package WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints
 */
class PerspectiveValidator extends ConstraintValidator
{
    /**
     * @var \Symfony\Component\Translation\TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var \Symfony\Component\Validator\ValidatorInterface $validator
     */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective $perspective
     * @param Constraint $perspectiveConstraint
     */
    public function validate($perspective, Constraint $perspectiveConstraint)
    {
        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints\Perspective $perspectiveConstraint
         */
        if ($this->context->getGroup() === $perspectiveConstraint::DEFAULT_GROUP) {
            $this->validatePerspectiveValue($perspective);
        } elseif ($this->context->getGroup() === $perspectiveConstraint::GROUP_PUBLIC_PERSPECTIVES) {
            $this->validatePublicPerspectives($perspective, $perspectiveConstraint);
        }
    }

    /**
     * @param \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective $perspective
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    protected function validatePerspectiveValue($perspective)
    {
        $queryConstraint = new Query();

        return $this->validator->validateValue(
            $perspective->getValue(),
            $queryConstraint,
            $queryConstraint::GROUP_PUBLIC_QUERIES
        );
    }

    /**
     * @param $perspective
     * @param Perspective $perspectiveConstraint
     */
    public function validatePublicPerspectives($perspective, Perspective $perspectiveConstraint)
    {
        $queryConstraintsViolationsList = $this->validatePerspectiveValue($perspective);
        if (count($queryConstraintsViolationsList) > 0) {
            $constraintsViolationsList = [];

            foreach ($queryConstraintsViolationsList as $constraintViolation) {
                /**
                 * @var $constraintViolation \Symfony\Component\Validator\ConstraintViolation
                 */
                $constraintsViolationsList[] = $constraintViolation->getMessage();
            }

            $parameters = ['{{ constrainsts_violations_list }}', implode($constraintsViolationsList)];
            $this->context->addViolationAt(
                'value',
                $this->translator->trans($perspectiveConstraint->queryConstraintViolation),
                $parameters
            );
        }

        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective $perspective
         */
        if ($perspective->getStatus() !== $perspective::STATUS_PUBLIC) {
            $this->context->addViolationAt(
                'status',
                $this->translator->trans($perspectiveConstraint->privacyViolation)
            );
        }
    }
} 