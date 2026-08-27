<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Http\Admin\Middleware;

use Laminas\InputFilter;
use Laminas\InputFilter\Exception\ExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\InputFilter\RuleDataFilter;
use Webware\Acl\RuleType;
use Webware\Core\Http\Middleware\HttpMethodProcessorTrait;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

final readonly class ProcessRuleMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function processDelete(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        /** @var InputFilter\InputFilterPluginManager $filterManager */
        $filterManager = $request->getAttribute(InputFilter\InputFilterPluginManager::class);
        $filter        = $filterManager->get(RuleDataFilter::class);
        $filter->setValidationGroup([
            'roleId',
            'resourceId',
        ]);
        $filter->setData($request->getAttributes());

        /** @var array{roleId: string, resourceId: string} $filteredData */
        $filteredData = $filter->getValues();

        $result = $this->commandBus->handle(new DeleteRuleCommand(...$filteredData));
        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('Rule deleted.');
        } else {
            $messenger?->warning('Rule could not be deleted. Please try again.');
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function processPatch(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        /** @var InputFilter\InputFilterPluginManager $filterManager */
        $filterManager = $request->getAttribute(InputFilter\InputFilterPluginManager::class);
        $filter        = $filterManager->get(RuleDataFilter::class);
        $filter->setValidationGroup([
            'roleId',
            'resourceId',
            'type',
        ]);
        $filter->setData($request->getParsedBody());

        if (! $filter->isValid()) {
            $messenger?->warning($filter->getSystemMessage());

            return $handler->handle($request);
        }

        /** @var array{roleId: string, resourceId: string, type: RuleType} $filteredData */
        $filteredData = $filter->getValues();

        $result = $this->commandBus->handle(new UpdateRuleTypeCommand(...$filteredData));
        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('Rule updated.');
        } else {
            $messenger?->warning('Rule update failed. Please try again.');
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        /** @var InputFilter\InputFilterPluginManager $filterManager */
        $filterManager = $request->getAttribute(InputFilter\InputFilterPluginManager::class);
        $filter        = $filterManager->get(RuleDataFilter::class);
        $filter->setValidationGroup([
            'roleId',
            'resourceId',
            'type',
            'assertions',
        ]);
        $filter->setData($request->getParsedBody());

        if (! $filter->isValid()) {
            $messenger?->warning($filter->getSystemMessage());

            return $handler->handle($request);
        }

        /** @var array{roleId: string, resourceId: string, type: RuleType, assertions: array<string>|null} $filteredData */
        $filteredData = $filter->getValues();

        $result = $this->commandBus->handle(
            new SaveRuleCommand(...$filteredData),
        );

        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('Rule saved.');
        } else {
            $messenger?->warning('Rule could not be saved. Please try again.');
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
