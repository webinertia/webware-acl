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

namespace Webware\Acl\Admin\Middleware;

use Laminas\InputFilter;
use Laminas\InputFilter\Exception\ExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\InputFilter\RoleDataFilter;
use Webware\Core\Http\Middleware\HttpMethodProcessorTrait;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

final readonly class ProcessRoleMiddleware implements MiddlewareInterface
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
        $filter        = $filterManager->get(RoleDataFilter::class);
        $filter->setValidationGroup([
            'roleId',
        ]);
        $filter->setData($request->getAttributes());

        /** @var array{roleId: string} $filteredData */
        $filteredData = $filter->getValues();

        $result = $this->commandBus->handle(new DeleteRoleCommand(...$filteredData));
        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('Role deleted.');
        } else {
            $messenger?->warning('Role could not be deleted. Please try again.');
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
        $filter        = $filterManager->get(RoleDataFilter::class);
        $filter->setValidationGroup([
            'id',
            'roleId',
            'parentId',
        ]);
        $filter->setData($request->getParsedBody());

        if (! $filter->isValid()) {
            $messenger?->warning($filter->getSystemMessage());

            return $handler->handle($request);
        }

        /** @var array{id: int|null, roleId: string, parentId: array<string>|null} $filteredData */
        $filteredData = $filter->getValues();

        $result = $this->commandBus->handle(new SaveRoleCommand(...$filteredData));
        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('Role saved.');
        } else {
            $messenger?->warning('Role could not be saved. Please try again.');
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
        $filter        = $filterManager->get(RoleDataFilter::class);
        $filter->setValidationGroup([
            'id',
            'roleId',
            'parentId',
        ]);
        $filter->setData($request->getParsedBody());

        if (! $filter->isValid()) {
            $messenger?->warning($filter->getSystemMessage());

            return $handler->handle($request);
        }

        /** @var array{id: int|null, roleId: string, parentId: array<string>|null} $filteredData */
        $filteredData = $filter->getValues();

        $result = $this->commandBus->handle(new SaveRoleCommand(...$filteredData));
        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('Role saved.');
        } else {
            $messenger?->warning('Role could not be saved. Please try again.');
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
