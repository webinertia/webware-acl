<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\RequestHandlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Http\RequestHandlers\ForbiddenHandler;
use Webware\Core\UserInterface;
use Webware\Message\SystemMessengerInterface;

#[CoversClass(ForbiddenHandler::class)]
final class ForbiddenHandlerTest extends TestCase
{
    #[Test]
    public function deniedUserDefaultsToSlash(): void
    {
        $handler  = new ForbiddenHandler('/login', null);
        $response = $handler->handle($this->request([UserInterface::class => $this->user('joe')]));

        self::assertSame('/', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function deniedUserFallsBackToReferer(): void
    {
        $handler  = new ForbiddenHandler('/login', null);
        $response = $handler->handle(
            $this->request([UserInterface::class => $this->user('joe')], ['HTTP_REFERER' => '/previous']),
        );

        self::assertSame('/previous', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function deniedUserIsWarnedAndRedirectedToForbiddenRedirect(): void
    {
        $handler   = new ForbiddenHandler('/login', '/denied');
        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects(self::once())
            ->method('warning')
            ->with(
                'You do not have permission to access the requested resource.',
                1,
                false,
            );

        $response = $handler->handle($this->request([
            UserInterface::class            => $this->user('joe'),
            SystemMessengerInterface::class => $messenger,
        ]));

        self::assertSame('/denied', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function forbiddenRedirectTakesPriorityOverReferer(): void
    {
        $handler  = new ForbiddenHandler('/login', '/denied');
        $response = $handler->handle(
            $this->request(
                [UserInterface::class => $this->user('joe')],
                ['HTTP_REFERER' => '/previous'],
            ),
        );

        self::assertSame('/denied', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function guestIsSilentlyRedirectedToLogin(): void
    {
        $handler  = new ForbiddenHandler();
        $response = $handler->handle($this->request([UserInterface::class => $this->user(null)]));

        self::assertSame('/login', $response->getHeaderLine('Location'));
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $serverParams
     */
    private function request(array $attributes, array $serverParams = []): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $name, mixed $default = null): mixed => $attributes[$name] ?? $default,
            );
        $request->method('getServerParams')->willReturn($serverParams);

        return $request;
    }

    private function user(?string $identity): UserInterface
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getIdentity')->willReturn($identity);

        return $user;
    }
}
