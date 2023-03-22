<?php
declare(strict_types=1);

namespace Admin\Application\Stats\DataGrid;

use MongoDB\BSON\ObjectId;
use Spatie\Cloneable\Cloneable;
use Bridge\Symfony\Form\DateTimeInterval\DateTimeIntervalData;
use FormManager\AbstractFormData;
use Bridge\Symfony\Form\DateTimeInterval\IntervalConstraint;

class CustomConstraintApplication extends AbstractFormData
{
    use Cloneable;
    public function __construct(
        // specify if target parameter should be required
        #[IntervalConstraint(['fromRequired' => true, 'toRequired' => false])]
        public ?DateTimeIntervalData $turnoverInterval = null,
        public ?ObjectId $ownerId = null,
        #[IntervalConstraint(['fromRequired' => true, 'toRequired' => false])]
        public ?DateTimeIntervalData $lastLogInterval = null,
    ) {
    }
}
