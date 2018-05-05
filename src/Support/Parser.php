<?php

namespace Nuwave\Lighthouse\Subscriptions\Support;

use GraphQL\Language\Parser as GraphQLParser;
use GraphQL\Language\AST\OperationDefinitionNode;
use Nuwave\Lighthouse\Subscriptions\Schema\Fields\SubscriptionField;

class Parser
{
    /**
     * Get handle for subscription field.
     *
     * @param  string $subscription
     * @return string
     */
    public static function handle($subscription)
    {
        return Protocol::FIELD_HANDLE.".".$subscription;
    }

    /**
     * Parse subscription field name from schema.
     *
     * @param  string $schema
     * @return string
     */
    public static function subscription($schema)
    {
        $document = GraphQLParser::parse($schema);
        $subscription = collect($document->definitions)
            ->filter(function ($node) {
                return $node instanceof OperationDefinitionNode
                    && $node->operation === 'subscription';
            })->first();

        return data_get($subscription, 'selectionSet.selections.0.name.value');
    }

    /**
     * Resolve subscription field instance.
     *
     * @param  string $schema
     * @return SubscriptionField
     */
    public static function resolve($schema)
    {
        $handle = static::handle(static::subscription($schema));

        return app($handle);
    }

    /**
     * Generate key for subscription.
     *
     * @param  array  $params
     * @param  string|null $subscription
     *
     * @return string
     */
    public static function key($params, $subscription = null)
    {
        return $subscription ? $subscription : static::subscription($params['query']);
    }
}
