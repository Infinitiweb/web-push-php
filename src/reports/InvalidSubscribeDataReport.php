<?php

namespace Minishlink\WebPush\reports;

use Minishlink\WebPush\Notification;

/**
 * Class InvalidSubscribeDataReport
 * @package Minishlink\WebPush\reports
 */
class InvalidSubscribeDataReport extends AbstractReport
{
    /** @var Notification */
    protected $notification;
    /** @var string */
    protected $message;

    /**
     * InvalidSubscribeDataException constructor.
     * @param Notification $notification
     * @param string $message
     */
    public function __construct(Notification $notification, string $message = '')
    {
        $this->notification = $notification;
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return Notification
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }
}
