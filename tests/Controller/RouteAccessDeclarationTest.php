<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * access_control only checks that the caller is logged in; who may use each action is declared
 * with #[IsGranted] on the action or its controller. Without one, any logged-in user could call
 * it, so a missing declaration has to fail here instead of reaching production.
 */
final class RouteAccessDeclarationTest extends KernelTestCase
{
    public function testEveryApiActionDeclaresWhoMayUseIt(): void
    {
        $undeclared = [];

        foreach (static::getContainer()->get(RouterInterface::class)->getRouteCollection() as $name => $route) {
            $controller = $route->getDefault('_controller');

            if (!\is_string($controller) || !str_starts_with($controller, 'App\\Controller\\')) {
                continue;
            }

            [$class, $method] = explode('::', $controller);

            if (!self::declaresAccess(new \ReflectionClass($class)) && !self::declaresAccess(new \ReflectionMethod($class, $method))) {
                $undeclared[] = $name . ' (' . $route->getPath() . ')';
            }
        }

        self::assertSame([], $undeclared, 'These actions need an #[IsGranted] on the action or its controller.');
    }

    private static function declaresAccess(\ReflectionClass|\ReflectionMethod $reflection): bool
    {
        return $reflection->getAttributes(IsGranted::class) !== [];
    }
}
