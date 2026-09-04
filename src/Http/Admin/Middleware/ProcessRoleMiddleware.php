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
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\InputFilter\RoleDataFilter;
use Webware\Core\Http\Middleware\HttpMethodProcessorTrait;
use Webware\Message\Exception\InvalidHopsValueException;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;

use function is_array;

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
        /** @var InputFilter\InputFilterPluginManager $filterManager */
        $filterManager = $request->getAttribute(InputFilter\InputFilterPluginManager::class);
        $filter        = $filterManager->get(RoleDataFilter::class);

        /** @var array{roleId: string} $values */
        $values = $filter->validate([
            'roleId' => $request->getAttribute('roleId'),
        ])->value();

        $result = $this->commandBus->handle(new DeleteRoleCommand(roleId: $values['roleId']));

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws InvalidHopsValueException
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

        $body         = $request->getParsedBody();
        $filterResult = $filter->validate(is_array($body) ? $body : []);

        if (! $filterResult->valid()) {
            $messenger?->warning($filter->getSystemMessage($filterResult->getMessages()));

            return $handler->handle($request);
        }

        /** @var array{id: int|null, roleId: string, parentId: array<string>|null} $values */
        $values = $filterResult->value();

        $result = $this->commandBus->handle(new SaveRoleCommand(
            id      : $values['id'],
            roleId  : $values['roleId'],
            parentId: $values['parentId'],
        ));

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws InvalidHopsValueException
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

        $body         = $request->getParsedBody();
        $filterResult = $filter->validate(is_array($body) ? $body : []);

        if (! $filterResult->valid()) {
            $messenger?->warning($filter->getSystemMessage($filterResult->getMessages()));

            return $handler->handle($request);
        }

        /** @var array{id: int|null, roleId: string, parentId: array<string>|null} $values */
        $values = $filterResult->value();

        $result = $this->commandBus->handle(new SaveRoleCommand(
            id      : $values['id'],
            roleId  : $values['roleId'],
            parentId: $values['parentId'],
        ));

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
