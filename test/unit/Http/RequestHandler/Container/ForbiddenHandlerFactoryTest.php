<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\RequestHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Http\RequestHandler\Container\ForbiddenHandlerFactory;
use Webware\Acl\Http\RequestHandler\ForbiddenHandler;
use Webware\Core\AclInterface;
use Webware\Core\UserInterface;

#[CoversClass(ForbiddenHandlerFactory::class)]
final class ForbiddenHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeReadsLoginPathAndForbiddenRedirectFromConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AclInterface::class  => ['forbidden_redirect' => '/denied', 'forbidden_template' => null],
                        UserInterface::class => ['login_path' => '/signin'],
                    ],
                ],
            ]);

        $handler = (new ForbiddenHandlerFactory())($container);

        self::assertSame('/signin', $handler->handle($this->request($this->user(null)))->getHeaderLine('Location'));
        self::assertSame('/denied', $handler->handle($this->request($this->user('joe')))->getHeaderLine('Location'));
    }

    #[Test]
    public function invokeUsesDefaultsWhenConfigMissing(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap([['config', []]]);

        $handler = (new ForbiddenHandlerFactory())($container);

        self::assertInstanceOf(ForbiddenHandler::class, $handler);
        self::assertSame('/login', $handler->handle($this->request($this->user(null)))->getHeaderLine('Location'));
        self::assertSame('/', $handler->handle($this->request($this->user('joe')))->getHeaderLine('Location'));
    }

    private function request(UserInterface $user): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnMap([
                [UserInterface::class, $user],
            ]);
        $request->method('getServerParams')->willReturn([]);

        return $request;
    }

    private function user(?string $identity): UserInterface
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getIdentity')->willReturn($identity);

        return $user;
    }
}
