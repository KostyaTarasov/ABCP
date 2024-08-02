<?php

namespace ABCP\Users;

interface ContractorInterface
{
    public static function getById(int $id): Contractor;
    public function getFullName(): string;
}