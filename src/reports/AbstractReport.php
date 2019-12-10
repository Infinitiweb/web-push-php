<?php

namespace Minishlink\WebPush\reports;

use Minishlink\WebPush\Notification;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AbstractReport
 * @package Minishlink\WebPush\reports
 */
abstract class AbstractReport implements IReport
{
    /**
     * @return RequestInterface|null
     */
    public function getRequest(): ?RequestInterface
    {
        return null;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getEndpoint(): ?string
    {
        return null;
    }

    /**
     * @return bool|null
     */
    public function isSubscriptionExpired(): ?bool
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getRequestPayload(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getResponseContent(): ?string
    {
        return null;
    }

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?array
    {
        return null;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return '';
    }

    /**
     * @return Notification|null
     */
    public function getNotification(): ?Notification
    {
        return null;
    }
}
