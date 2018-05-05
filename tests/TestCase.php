<?php

namespace Tests;

use GraphQL\Language\Parser;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Nuwave\Lighthouse\Providers\LighthouseServiceProvider::class,
            \Nuwave\Lighthouse\Subscriptions\SubscriptionServiceProvider::class
        ];
    }

    /**
     * Get package aliases.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set(
            'lighthouse.schema.register',
            realpath(__DIR__.'/support/schema.graphql')
        );
    }

    /**
     * Parse raw schema.
     *
     * @param string $schema
     *
     * @return \GraphQL\Language\AST\DocumentNode
     */
    protected function parse(string $schema)
    {
        return Parser::parse($schema);
    }
}
