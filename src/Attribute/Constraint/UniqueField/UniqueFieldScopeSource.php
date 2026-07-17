<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

enum UniqueFieldScopeSource
{
    /** Value comes from the current request's route attributes (path param). */
    case RouteParam;

    /** Value comes from another property on the object being validated. */
    case PropertyPath;
}
