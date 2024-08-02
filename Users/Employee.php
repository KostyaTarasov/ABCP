<?php

namespace ABCP\Users;

class Employee extends Contractor
{
    // Здесь должен быть код для получения модели из базы данных
    public function __construct()
    {
        $this->id = 2;
        $this->name = 'Анна Андреева';
        $this->type = self::TYPE_CUSTOMER;
        $this->mobile = '8-987-654-3210';
        $this->email = 'test2@example.com';
    }
}