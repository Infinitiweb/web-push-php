<?php

namespace Minishlink\WebPush\reports;

use Minishlink\WebPush\Notification;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface IReport
 * @package Minishlink\WebPush\reports
 */
interface IReport
{
    /**
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * @return bool
     */
    public function isValid(): bool;

    /**
     * @return RequestInterface|null
     */
    public function getRequest(): ?RequestInterface;

    /**
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface;

    /**
     * @return string|null
     */
    public function getEndpoint(): ?string;

    /**
     * @return bool|null
     */
    public function isSubscriptionExpired(): ?bool;

    /**
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * @return string|null
     */
    public function getRequestPayload(): ?string;

    /**
     * @return string|null
     */
    public function getResponseContent(): ?string;

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?array;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @return Notification|null
     */
    public function getNotification(): ?Notification;
}
