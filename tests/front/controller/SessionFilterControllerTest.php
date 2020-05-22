<?php

declare(strict_types=1);

namespace Shaarli\Front\Controller;

use PHPUnit\Framework\TestCase;
use Shaarli\Security\SessionManager;
use Slim\Http\Request;
use Slim\Http\Response;

class SessionFilterControllerTest extends TestCase
{
    use FrontControllerMockHelper;

    /** @var SessionFilterController */
    protected $controller;

    public function setUp(): void
    {
        $this->createContainer();

        $this->controller = new SessionFilterController($this->container);
    }

    /**
     * Link per page - Default call with valid parameter and a referer.
     */
    public function testLinksPerPage(): void
    {
        $this->createValidContainerMockSet();

        $this->container->environment = ['HTTP_REFERER' => 'http://shaarli/subfolder/controller/?searchtag=abc'];

        $request = $this->createMock(Request::class);
        $request->method('getParam')->with('nb')->willReturn('8');
        $response = new Response();

        $this->container->sessionManager
            ->expects(static::once())
            ->method('setSessionParameter')
            ->with(SessionManager::KEY_LINKS_PER_PAGE, 8)
        ;

        $result = $this->controller->linksPerPage($request, $response);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['/subfolder/controller/?searchtag=abc'], $result->getHeader('location'));
    }

    /**
     * Link per page - Invalid value, should use default value (20)
     */
    public function testLinksPerPageNotValid(): void
    {
        $this->createValidContainerMockSet();

        $request = $this->createMock(Request::class);
        $request->method('getParam')->with('nb')->willReturn('test');
        $response = new Response();

        $this->container->sessionManager
            ->expects(static::once())
            ->method('setSessionParameter')
            ->with(SessionManager::KEY_LINKS_PER_PAGE, 20)
        ;

        $result = $this->controller->linksPerPage($request, $response);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['./'], $result->getHeader('location'));
    }

    /**
     * Visibility - Default call for private filter while logged in without current value
     */
    public function testVisibility(): void
    {
        $this->createValidContainerMockSet();

        $arg = ['visibility' => 'private'];

        $this->container->environment = ['HTTP_REFERER' => 'http://shaarli/subfolder/controller/?searchtag=abc'];

        $this->container->loginManager->method('isLoggedIn')->willReturn(true);
        $this->container->sessionManager
            ->expects(static::once())
            ->method('setSessionParameter')
            ->with(SessionManager::KEY_VISIBILITY, 'private')
        ;

        $request = $this->createMock(Request::class);
        $response = new Response();

        $result = $this->controller->visibility($request, $response, $arg);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['/subfolder/controller/?searchtag=abc'], $result->getHeader('location'));
    }

    /**
     * Visibility - Toggle off private visibility
     */
    public function testVisibilityToggleOff(): void
    {
        $this->createValidContainerMockSet();

        $arg = ['visibility' => 'private'];

        $this->container->environment = ['HTTP_REFERER' => 'http://shaarli/subfolder/controller/?searchtag=abc'];

        $this->container->loginManager->method('isLoggedIn')->willReturn(true);
        $this->container->sessionManager
            ->method('getSessionParameter')
            ->with(SessionManager::KEY_VISIBILITY)
            ->willReturn('private')
        ;
        $this->container->sessionManager
            ->expects(static::never())
            ->method('setSessionParameter')
        ;
        $this->container->sessionManager
            ->expects(static::once())
            ->method('deleteSessionParameter')
            ->with(SessionManager::KEY_VISIBILITY)
        ;

        $request = $this->createMock(Request::class);
        $response = new Response();

        $result = $this->controller->visibility($request, $response, $arg);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['/subfolder/controller/?searchtag=abc'], $result->getHeader('location'));
    }

    /**
     * Visibility - Change private to public
     */
    public function testVisibilitySwitch(): void
    {
        $this->createValidContainerMockSet();

        $arg = ['visibility' => 'private'];

        $this->container->loginManager->method('isLoggedIn')->willReturn(true);
        $this->container->sessionManager
            ->method('getSessionParameter')
            ->with(SessionManager::KEY_VISIBILITY)
            ->willReturn('public')
        ;
        $this->container->sessionManager
            ->expects(static::once())
            ->method('setSessionParameter')
            ->with(SessionManager::KEY_VISIBILITY, 'private')
        ;

        $request = $this->createMock(Request::class);
        $response = new Response();

        $result = $this->controller->visibility($request, $response, $arg);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['./'], $result->getHeader('location'));
    }

    /**
     * Visibility - With invalid value - should remove any visibility setting
     */
    public function testVisibilityInvalidValue(): void
    {
        $this->createValidContainerMockSet();

        $arg = ['visibility' => 'test'];

        $this->container->environment = ['HTTP_REFERER' => 'http://shaarli/subfolder/controller/?searchtag=abc'];

        $this->container->loginManager->method('isLoggedIn')->willReturn(true);
        $this->container->sessionManager
            ->expects(static::never())
            ->method('setSessionParameter')
        ;
        $this->container->sessionManager
            ->expects(static::once())
            ->method('deleteSessionParameter')
            ->with(SessionManager::KEY_VISIBILITY)
        ;

        $request = $this->createMock(Request::class);
        $response = new Response();

        $result = $this->controller->visibility($request, $response, $arg);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['/subfolder/controller/?searchtag=abc'], $result->getHeader('location'));
    }

    /**
     * Visibility - Try to change visibility while logged out
     */
    public function testVisibilityLoggedOut(): void
    {
        $this->createValidContainerMockSet();

        $arg = ['visibility' => 'test'];

        $this->container->environment = ['HTTP_REFERER' => 'http://shaarli/subfolder/controller/?searchtag=abc'];

        $this->container->loginManager->method('isLoggedIn')->willReturn(false);
        $this->container->sessionManager
            ->expects(static::never())
            ->method('setSessionParameter')
        ;
        $this->container->sessionManager
            ->expects(static::never())
            ->method('deleteSessionParameter')
            ->with(SessionManager::KEY_VISIBILITY)
        ;

        $request = $this->createMock(Request::class);
        $response = new Response();

        $result = $this->controller->visibility($request, $response, $arg);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['/subfolder/controller/?searchtag=abc'], $result->getHeader('location'));
    }

    /**
     * Untagged only - valid call
     */
    public function testUntaggedOnly(): void
    {
        $this->createValidContainerMockSet();

        $this->container->environment = ['HTTP_REFERER' => 'http://shaarli/subfolder/controller/?searchtag=abc'];

        $request = $this->createMock(Request::class);
        $response = new Response();

        $this->container->sessionManager
            ->expects(static::once())
            ->method('setSessionParameter')
            ->with(SessionManager::KEY_UNTAGGED_ONLY, true)
        ;

        $result = $this->controller->untaggedOnly($request, $response);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['/subfolder/controller/?searchtag=abc'], $result->getHeader('location'));
    }

    /**
     * Untagged only - toggle off
     */
    public function testUntaggedOnlyToggleOff(): void
    {
        $this->createValidContainerMockSet();

        $this->container->environment = ['HTTP_REFERER' => 'http://shaarli/subfolder/controller/?searchtag=abc'];

        $request = $this->createMock(Request::class);
        $response = new Response();

        $this->container->sessionManager
            ->method('getSessionParameter')
            ->with(SessionManager::KEY_UNTAGGED_ONLY)
            ->willReturn(true)
        ;
        $this->container->sessionManager
            ->expects(static::once())
            ->method('setSessionParameter')
            ->with(SessionManager::KEY_UNTAGGED_ONLY, false)
        ;

        $result = $this->controller->untaggedOnly($request, $response);

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(302, $result->getStatusCode());
        static::assertSame(['/subfolder/controller/?searchtag=abc'], $result->getHeader('location'));
    }
}
