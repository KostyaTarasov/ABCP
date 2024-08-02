<?php

namespace ABCP\Users;

class Seller extends Contractor
{
    // Здесь должен быть код для получения модели из базы данных
    public function __construct()
    {
        $this->id = 1;
        $this->name = 'Екатерина Ескина';
        $this->type = self::TYPE_CUSTOMER;
        $this->mobile = '8-123-456-7890';
        $this->email = 'test1@example.com';
    }
}