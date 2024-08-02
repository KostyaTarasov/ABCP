<?php

namespace ABCP\Users;

class Contractor implements ContractorInterface
{
    const TYPE_CUSTOMER = 0;
    public int $id;
    public int $type;
    public string $name;
    public string $email;
    public string $mobile;

    public function __construct(int $id, int $type, string $name, string $email, string $mobile)
    {
        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
        $this->email = $email;
        $this->mobile = $mobile;
    }

    public static function getById(int $id): Contractor
    {
        // Здесь должен быть код для получения модели из базы данных
        return new self($id, self::TYPE_CUSTOMER, 'Default Name', 'testexample.com', '8-800-456-5000');
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public static function getResellerEmailFrom(): string
    {
        return 'contractor@example.com';
    }

    public static function getEmailsByPermit(int $resellerId, string $event): array
    {
        return ['someemail@example.com', 'someemail2@example.com'];
    }
}
