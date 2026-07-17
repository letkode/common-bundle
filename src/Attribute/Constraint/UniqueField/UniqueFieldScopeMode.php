<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

enum UniqueFieldScopeMode
{
    /** Narrows the uniqueness lookup — the resolved value must match an associated entity on $entityField. */
    case Filter;

    /** Narrows the uniqueness lookup — the resolved value is used directly as a scalar criterion on $entityField (no entity lookup). */
    case ScalarFilter;

    /** Excludes a match from the violation — if $entityField on the found record equals the resolved value, it's not a duplicate. */
    case Exclude;
}
