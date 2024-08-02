<?php

namespace ABCP;

abstract class AbstractReferencesOperation
{
    abstract public function doOperation(): array;

    public function getRequest(string $parameterName): array
    {
        return (array)$_REQUEST[$parameterName] ?? [];
    }
}