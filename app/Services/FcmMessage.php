<?php


namespace App\Services;


class FcmMessage extends \NotificationChannels\Fcm\FcmMessage
{
    public static function create(): self
    {
        return new FcmMessage();
    }

    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'data' => $this->getData(),
            'notification' => !is_null($this->getNotification()) ? $this->getNotification()->toArray() : null,
            'android' => !is_null($this->getAndroid()) ? $this->getAndroid()->toArray() : null,
            'webpush' => !is_null($this->getWebpush()) ? $this->getWebpush()->toArray() : null,
            'apns' => !is_null($this->getApns()) ? $this->getApns()->toArray() : null,
            'fcm_options' => !is_null($this->getFcmOptions()) ? $this->getFcmOptions()->toArray() : null,
            'token' => $this->getToken(),
        ];
    }
}
