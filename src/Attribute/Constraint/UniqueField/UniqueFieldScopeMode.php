<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

enum UniqueFieldScopeMode
{
    /** Narrows the uniqueness lookup — the resolved value must match an associated entity on $entityField. */
    case Filter;

    /** Excludes a match from the violation — if $entityField on the found record equals the resolved value, it's not a duplicate. */
    case Exclude;
}
