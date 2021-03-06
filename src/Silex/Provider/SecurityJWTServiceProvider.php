<?php

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Component\Security\Core\Encoder\JWTEncoder;
use Silex\Component\Security\Http\Authentication;
use Silex\Component\Security\Http\Authentication\Provider\JWTProvider;
use Silex\Component\Security\Http\Firewall\JWTListener;
use Silex\Component\Security\Http\Logout\LogoutSuccessHandler;

class SecurityJWTServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        $app['security.jwt'] = array_merge([
            'secret_key' => 'default_secret_key',
            'life_time' => 86400,
            'options' => [
                'header_name' => 'SECURITY_TOKEN_HEADER'
            ]
        ], $app['security.jwt']);

        $app['security.jwt.encoder'] = function() use ($app) {
            return new JWTEncoder($app['security.jwt']['secret_key'], $app['security.jwt']['life_time']);
        };

        $app['security.authentication.success_handler.secured'] = function () use ($app) {
            return new Authentication\AuthenticationSuccessHandler($app['security.http_utils'], []);
        };

        $app['security.authentication.failure_handler.secured'] = function () use ($app) {
            return new Authentication\AuthenticationFailureHandler($app['request'], $app['security.http_utils'], []);
        };

        $app['security.authentication.logout_handler.secured'] = function () use ($app) {
            return new LogoutSuccessHandler($app['security.http_utils'], []);
        };

        /**
         * Class for usage custom listeners
         */
        $app['security.jwt.authentication_listener'] = function() use ($app) {
            return new JWTListener($app['security'],
                $app['security.authentication_manager'],
                $app['security.jwt.encoder'],
                $app['security.jwt']['options']
            );
        };

        /**
         * Class for usage custom user provider
         */
        $app['security.jwt.authentication_provider'] = function() use ($app) {
            return new JWTProvider($app['users']);
        };

        $app['security.authentication_listener.factory.jwt'] = $app->protect(function ($name, $options) use ($app) {
            $app['security.authentication_listener.'.$name.'.jwt'] = function() use ($app){
                return $app['security.jwt.authentication_listener'];
            };
            $app['security.authentication_provider.' . $name . '.jwt'] = function() use ($app){
                return $app['security.jwt.authentication_provider'];
            };
            return array(
                'security.authentication_provider.'.$name.'.jwt',
                'security.authentication_listener.'.$name.'.jwt',
                null,
                'pre_auth'
            );
        });
    }
}