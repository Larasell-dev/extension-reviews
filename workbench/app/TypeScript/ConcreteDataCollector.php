<?php

namespace Workbench\App\TypeScript;

use ReflectionClass;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptCollector;
use Spatie\TypeScriptTransformer\Structures\TransformedType;

/**
 * Skips abstract Data bases; only their concrete subclasses are emitted.
 */
class ConcreteDataCollector extends DataTypeScriptCollector
{
    public function getTransformedType(ReflectionClass $class): ?TransformedType
    {
        return $class->isAbstract() ? null : parent::getTransformedType($class);
    }
}
