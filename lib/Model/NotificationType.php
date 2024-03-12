<?php

namespace OCA\AnnouncementCenter\Model;

class NotificationType
{
    protected $notificationTypes;

    public function __construct()
    {
        $this->notificationTypes = [
            'activities' => 0,
            'notifications' => 1,
            'email' => 2,
        ];
    }

    private function isTypeSet(int $value, string $notificationType) : bool
    {
        $offset = $this->notificationTypes[$notificationType];
        return ($value & (1 << $offset)) > 0;
    }

    private function getTypeMask(bool $booleanValue, string $notificationType) : int
    {
        $offset = $this->notificationTypes[$notificationType];
        return ((int)$booleanValue) << $offset;
    }

    public function getActivities(int $value) : bool
    {
        return $this->isTypeSet($value, 'activities');
    }

    public function getNotifications(int $value) : bool
    {
        return $this->isTypeSet($value, 'notifications');
    }

    public function getEmail(int $value) : bool
    {
        return $this->isTypeSet($value, 'email');
    }

    public function setNotificationTypes(bool $activities, bool $notifications, bool $email) : int
    {
        $value = 0;
        $value += $this->getTypeMask($activities, 'activities');
        $value += $this->getTypeMask($notifications, 'notifications');
        $value += $this->getTypeMask($email, 'email');
        return $value;
    }   
}
