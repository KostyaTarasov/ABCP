<?php

namespace ABCP\Services\Notification;

use ABCP\Users\Contractor;
use ABCP\Users\Seller;
use ABCP\Operation;
use MessagesClient;
use NotificationManager;

class NotificationService
{
    const EVENTS_CHANGE_RETURN_STATUS = 'changeReturnStatus';

    public function sendNotifications(Seller $reseller, Contractor $client, int $notificationType, array $templateData): array
    {
        $result = $this->initializeResult();
        $this->sendEmployeeNotifications($reseller, $templateData, $result);
        $this->sendClientNotifications($reseller, $client, $notificationType, $templateData, $result);

        return $result;
    }

    private function initializeResult(): array
    {
        return [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail' => false,
            'notificationClientBySms' => [
                'isSent' => false,
                'message' => '',
            ],
        ];
    }

    private function sendEmployeeNotifications(Seller $reseller, array $templateData, array &$result): void
    {
        $emailFrom = Contractor::getResellerEmailFrom();
        $emails = Contractor::getEmailsByPermit($reseller->id, 'tsGoodsReturn');

        if (!empty($emailFrom) && !empty($emails)) {
            foreach ($emails as $email) {
                MessagesClient::sendMessage([
                    0 => [
                        'emailFrom' => $emailFrom,
                        'emailTo' => $email,
                        'subject' => __('complaintEmployeeEmailSubject', $templateData, $reseller->id),
                        'message' => __('complaintEmployeeEmailBody', $templateData, $reseller->id),
                    ],
                ], $reseller->id, self::EVENTS_CHANGE_RETURN_STATUS);
                $result['notificationEmployeeByEmail'] = true;
            }
        }
    }

    private function sendClientNotifications(Seller $reseller, Contractor $client, int $notificationType, array $templateData, array &$result): void
    {
        if ($notificationType === Operation::TYPE_CHANGE && !empty($templateData['DIFFERENCES'])) {
            $this->sendClientEmailNotification($reseller, $client, $templateData, $result);
            $this->sendClientSmsNotification($reseller, $client, $templateData, $result);
        }
    }

    private function sendClientEmailNotification(Seller $reseller, Contractor $client, array $templateData, array &$result): void
    {
        $emailFrom = Contractor::getResellerEmailFrom();
        if (!empty($emailFrom) && !empty($client->email)) {
            MessagesClient::sendMessage([
                0 => [
                    'emailFrom' => $emailFrom,
                    'emailTo' => $client->email,
                    'subject' => __('complaintClientEmailSubject', $templateData, $reseller->id),
                    'message' => __('complaintClientEmailBody', $templateData, $reseller->id),
                ],
            ], $reseller->id, $client->id, self::EVENTS_CHANGE_RETURN_STATUS, (int)$templateData['DIFFERENCES']);
            $result['notificationClientByEmail'] = true;
        }
    }

    private function sendClientSmsNotification(Seller $reseller, Contractor $client, array $templateData, array &$result): void
    {
        if (!empty($client->mobile)) {
            if (NotificationManager::send($reseller->id, $client->id, self::EVENTS_CHANGE_RETURN_STATUS, (int)$templateData['DIFFERENCES'], $templateData, '')) {
                $result['notificationClientBySms']['isSent'] = true;
            } else {
                $result['notificationClientBySms']['message'] = 'Failed to send SMS';
            }
        }
    }
}
