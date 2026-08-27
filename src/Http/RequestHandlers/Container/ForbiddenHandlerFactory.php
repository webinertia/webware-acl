<?php

declare(strict_types=1);

namespace Webware\Acl\Http\RequestHandlers\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\RequestHandlers\ForbiddenHandler;
use Webware\Core\AclInterface;
use Webware\Core\UserInterface;

/**
 * Creates the default ForbiddenHandler.
 *
 * To substitute a custom denial handler, bind your own class to
 * ForbiddenHandlerInterface in your application's DI config:
 *
 * ```php
 * // config/autoload/acl.local.php
 * use Webware\Acl\Http\RequestHandlers\ForbiddenHandlerInterface;
 *
 * return [
 *     'dependencies' => [
 *         'aliases' => [
 *             ForbiddenHandlerInterface::class => MyCustomForbiddenHandler::class,
 *         ],
 *     ],
 * ];
 * ```
 */
final class ForbiddenHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ForbiddenHandler
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        /** @var array<string, mixed> $acl */
        $acl = $config[AclInterface::class] ?? [];
        /** @var array<string, mixed> $user */
        $user = $config[UserInterface::class] ?? [];

        return new ForbiddenHandler(
            loginPath        : (string) ($user['login_path'] ?? '/login'),
            forbiddenRedirect: ($acl['forbidden_redirect'] ?? '/') === '' ? null : $acl['forbidden_redirect'] ?? '/',
            forbiddenTemplate: $acl['forbidden_template'] ?? null ?: null,
        );
    }
}
