<?php

namespace Minishlink\WebPush\forms;

use Minishlink\WebPush\Notification;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestForm
 * @package Minishlink\WebPush\forms
 */
class RequestForm
{
    /** @var RequestInterface|null */
    protected $request;
    /** @var Notification|null */
    protected $notification;
    /** @var bool */
    protected $isValid;

    /**
     * RequestForm constructor.
     * @param bool $isValid
     * @param RequestInterface|null $request
     * @param Notification|null $notification
     */
    public function __construct(bool $isValid, ?RequestInterface $request = null, ?Notification $notification = null)
    {
        $this->isValid = $isValid;
        $this->request = $request;
        $this->notification = $notification;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return RequestInterface|null
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * @return Notification|null
     */
    public function getNotification(): ?Notification
    {
        return $this->notification;
    }
}
