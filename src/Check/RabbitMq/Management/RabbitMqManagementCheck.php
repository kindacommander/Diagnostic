<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\Management;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Check access to RabbitMQ Management via API.
 */
class RabbitMqManagementCheck implements CheckInterface
{
    /**
     * @var HttpAdapterInterface
     */
    private HttpAdapterInterface $http;

    /**
     * @var RabbitMqConnectionParameters
     */
    private RabbitMqConnectionParameters $connectionParameters;

    /**
     * Constructor.
     *
     * @param RabbitMqConnectionParameters $connectionParameters
     * @param HttpAdapterInterface|null    $http
     */
    public function __construct(RabbitMqConnectionParameters $connectionParameters, HttpAdapterInterface $http = null)
    {
        $this->connectionParameters = $connectionParameters;
        $this->http = $http ?: new HttpAdapter();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $url = \sprintf(
            '%s/api/overview',
            $this->connectionParameters->getDsn(true, false)
        );

        $request = $this->http->createRequest('GET', $url, [
            'accept' => 'application/json',
        ]);

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return new Failure(\sprintf(
                'Fail connect to RabbitMQ Management API. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        if ($response->getStatusCode() !== 200) {
            return new Failure(\sprintf(
                'Fail connect to RabbitMQ Management API. Return wrong status code - %d.',
                $response->getStatusCode()
            ));
        }

        return new Success('Success connect to RabbitMQ Management API.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'dsn' => $this->connectionParameters->getDsn(true, true),
        ];
    }
}
