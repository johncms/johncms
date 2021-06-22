<?php

namespace PHPSTORM_META {

    override(
        \di(0),
        map(
            [
                ''         => '@',
                'config'   => 'array',
                'counters' => \Johncms\Counters::class,
            ]
        )
    );
    override(
        \Psr\Container\ContainerInterface::get(0),
        map(
            [
                ''         => '@',
                'config'   => 'array',
                'counters' => \Johncms\Counters::class,
            ]
        )
    );

    override(\League\Route\RouteConditionHandlerTrait::getHost(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\RouteConditionHandlerTrait::getName(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\RouteConditionHandlerTrait::getPort(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\RouteConditionHandlerTrait::getScheme(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\RouteConditionHandlerTrait::setHost(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\RouteConditionHandlerTrait::setName(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\RouteConditionHandlerTrait::setPort(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\RouteConditionHandlerTrait::setScheme(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::getMiddlewareStack(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::lazyMiddleware(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::lazyMiddlewares(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::lazyPrependMiddleware(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::middleware(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::middlewares(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::prependMiddleware(0), map(['' => \League\Route\Route::class]));
    override(\League\Route\Middleware\MiddlewareAwareTrait::shiftMiddleware(0), map(['' => \League\Route\Route::class]));
}
