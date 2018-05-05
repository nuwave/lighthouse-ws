<?php

namespace Nuwave\Lighthouse\Subscriptions\Schema\Directives;

use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Traits\HandlesDirectives;
use Nuwave\Lighthouse\Support\Exceptions\DirectiveException;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Subscriptions\Schema\Fields\SubscriptionField;
use Nuwave\Lighthouse\Subscriptions\Support\Parser;
use GraphQL\Error\Error;

class SubscriptionDirective implements FieldResolver
{
    use HandlesDirectives;

    /**
     * Name of the directive.
     *
     * @return string
     */
    public function name()
    {
        return 'subscription';
    }

    /**
     * Resolve the field directive.
     *
     * @param FieldValue $value
     *
     * @return FieldValue
     */
    public function resolveField(FieldValue $value)
    {
        $field = $this->subscriptionField($value);

        return $value->setResolver(function ($root, array $args, $context, $info) use ($field) {
            return $field->resolve($root, $args, $context, $info);
        });
    }

    /**
     * Get subscription field.
     *
     * @param  FieldValue $value
     * @return SubscriptionField
     */
    protected function subscriptionField(FieldValue $value)
    {
        $handle = Parser::handle($value->getFieldName());
        $instance = $this->directiveArgValue(
            $this->fieldDirective($value->getField(), $this->name()),
            'resolve'
        );

        if (! $instance) {
            throw new DirectiveException('The `subscription` directive must have a `resolve` argument.');
        }

        return app()->instance($handle, app($instance));
    }
}
