<?php
declare(strict_types=1);

namespace Bridge\Symfony\Form\DateTimeInterval;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CustomConstraint extends Constraint
{
    public bool $fromRequired = true;
    public bool $toRequired = true;
    public string $messageFromRequired = 'interval.value.from.required';
    public string $messageToRequired = 'interval.value.to.required';
    public string $messageWrongOrder = 'interval.wrong_order';

    public function __construct($options)
    {
        parent::__construct($options);
    }

    public function validatedBy() : string
    {
        return IntervalConstraintValidator::class;
    }

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
