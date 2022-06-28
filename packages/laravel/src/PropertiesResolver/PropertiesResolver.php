<?php

namespace Monolikit\PropertiesResolver;

interface PropertiesResolver
{
    /**
     * Resolves the properties for the given component.
     */
    public function resolve(string $component, array $properties): array;
}
