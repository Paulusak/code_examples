<?php
declare(strict_types=1);

namespace Bridge\Symfony\Form\DateTimeInterval;

use Bridge\Symfony\Form\IntegerInterval\IntegerIntervalData;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CustomConstraintValidator extends ConstraintValidator
{
    /**
     * @param IntervalConstraint                            $constraint
     * @param DateTimeIntervalData|IntegerIntervalData|null $value
     */
    public function validate($value, Constraint $constraint) : void
    {
        if ($value === null) {
            return;
        }

        $from = $value->from;
        $to = $value->to;

        if ($constraint->fromRequired === true && $from === null) {
            $this->context->buildViolation($constraint->messageFromRequired)->setTranslationDomain('validators')->addViolation();
        }

        if ($constraint->toRequired === true && $to === null) {
            $this->context->buildViolation($constraint->messageToRequired)->setTranslationDomain('validators')->addViolation();
        }

        if ($from !== null && $to !== null && $from > $to) {
            $this->context->buildViolation($constraint->messageWrongOrder)->setTranslationDomain('validators')->addViolation();
        }
    }
}
