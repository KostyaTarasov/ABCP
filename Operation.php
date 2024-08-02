<?php

namespace ABCP;

use ABCP\Exceptions\DbException;
use ABCP\Services\Notification\NotificationService;
use ABCP\Status\Status;
use ABCP\Users\Contractor;
use ABCP\Users\Employee;
use ABCP\Users\Seller;

class Operation extends AbstractReferencesOperation
{
    public const TYPE_NEW = 1;
    public const TYPE_CHANGE = 2;

    public function doOperation(): array
    {
        $data = $this->getRequestData();
        $this->validateData($data);
        $reseller = $this->getReseller($data['resellerId']);
        $client = $this->getClient($data['clientId'], $reseller->id);
        $creator = $this->getEmployee($data['creatorId']);
        $expert = $this->getEmployee($data['expertId']);
        $differences = $this->getDifferences($data);
        $templateData = $this->prepareTemplateData($data, $creator, $expert, $client, $differences);
        $this->validateTemplateData($templateData);

        return (new NotificationService())->sendNotifications($reseller, $client, $data['notificationType'], $templateData);
    }

    private function getRequestData(): array
    {
        return $this->getRequest('data');
    }

    private function validateData(array $data): void
    {
        // Фильтрация и валидация данных
        $data = filter_var_array($data, [
            'resellerId'       => FILTER_VALIDATE_INT,
            'notificationType' => FILTER_VALIDATE_INT,
            'clientId'         => FILTER_VALIDATE_INT,
            'creatorId'        => FILTER_VALIDATE_INT,
            'expertId'         => FILTER_VALIDATE_INT,
            'complaintId'      => FILTER_VALIDATE_INT,
            'complaintNumber'  => FILTER_VALIDATE_INT,
            'consumptionId'    => FILTER_VALIDATE_INT,
            'consumptionNumber'=> FILTER_VALIDATE_INT,
            'agreementNumber'  => FILTER_VALIDATE_INT,
            'date'             => FILTER_SANITIZE_STRING,
            'differences'      => [
                'filter' => FILTER_CALLBACK,
                'options' => function($value) {
                    return is_array($value) ? array_map('intval', $value) : null;
                }
            ]
        ]);

        if (empty($data['resellerId'])) {
            throw new \InvalidArgumentException('Invalid or empty resellerId', 400);
        }

        if (empty($data['notificationType'])) {
            throw new \InvalidArgumentException('Invalid or empty notificationType', 400);
        }

        if (empty($data['clientId'])) {
            throw new \InvalidArgumentException('Invalid or empty clientId', 400);
        }

        if (empty($data['creatorId'])) {
            throw new \InvalidArgumentException('Invalid or empty creatorId', 400);
        }

        if (empty($data['expertId'])) {
            throw new \InvalidArgumentException('Invalid or empty expertId', 400);
        }
    }

    private function getReseller(int $resellerId): Seller
    {
        $reseller = Seller::getById($resellerId);
        if (empty($reseller)) {
            throw new DbException('Seller not found!', 400);
        }

        return $reseller;
    }

    private function getClient(int $clientId, int $resellerId): Contractor
    {
        $client = Contractor::getById($clientId);
        if (empty($client) || $client->type !== Contractor::TYPE_CUSTOMER || $client->id !== $resellerId) {
            throw new DbException('Client not found!', 400);
        }

        return $client;
    }

    private function getEmployee(int $employeeId): Employee
    {
        $employee = Employee::getById($employeeId);
        if (empty($employee)) {
            throw new DbException('Employee not found!', 400);
        }

        return $employee;
    }

    private function getDifferences(array $data): string
    {
        if ($data['notificationType'] === self::TYPE_NEW) {
            return $this->getNewPositionMessage($data['resellerId']);
        } elseif ($data['notificationType'] === self::TYPE_CHANGE && !empty($data['differences'])) {
            return $this->getChangePositionMessage($data['differences'], $data['resellerId']);
        }

        return '';
    }

    private function getNewPositionMessage(int $resellerId): string
    {
        return __('NewPositionAdded', null, $resellerId);
    }

    private function getChangePositionMessage(array $differences, int $resellerId): string
    {
        return __('PositionStatusHasChanged', [
            'FROM' => Status::getName((int)$differences['from']),
            'TO' => Status::getName((int)$differences['to']),
        ], $resellerId);
    }

    private function prepareTemplateData(array $data, Employee $creator, Employee $expert, Contractor $client, string $differences): array
    {
        return [
            'COMPLAINT_ID' => (int)$data['complaintId'],
            'COMPLAINT_NUMBER' => (int)$data['complaintNumber'],
            'CREATOR_ID' => $creator->id,
            'CREATOR_NAME' => htmlspecialchars((string)$creator->getFullName(), ENT_QUOTES, 'UTF-8'),
            'EXPERT_ID' => $expert->id,
            'EXPERT_NAME' =>  htmlspecialchars((string)$expert->getFullName(), ENT_QUOTES, 'UTF-8'),
            'CLIENT_ID' => $client->id,
            'CLIENT_NAME' => htmlspecialchars((string)$client->getFullName(), ENT_QUOTES, 'UTF-8'),
            'CONSUMPTION_ID' => (int)$data['consumptionId'],
            'CONSUMPTION_NUMBER' => (int)$data['consumptionNumber'],
            'AGREEMENT_NUMBER' => (int)$data['agreementNumber'],
            'DATE' => htmlspecialchars((string)$data['date'], ENT_QUOTES, 'UTF-8'),,
            'DIFFERENCES' => htmlspecialchars($differences, ENT_QUOTES, 'UTF-8'),,
        ];
    }

    private function validateTemplateData(array $templateData): void
    {
        foreach ($templateData as $key => $value) {
            if (empty($value)) {
                throw new \InvalidArgumentException("Template Data ({$key}) is empty!", 500);
            }
        }
    }
}
