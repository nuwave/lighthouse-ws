<?php

namespace Nuwave\Lighthouse\Subscriptions\Schema\Directives;

use Nuwave\Lighthouse\Support\Traits\HandlesDirectives;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Exceptions\DirectiveException;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Publisher;

class WebsocketDirective implements FieldMiddleware
{
    use HandlesDirectives;

    /**
     * Name of the directive.
     *
     * @return string
     */
    public function name()
    {
        return 'websocket';
    }

    /**
     * Resolve the field directive.
     *
     * @param FieldValue $value
     *
     * @return FieldValue
     */
    public function handleField(FieldValue $value)
    {
        $resolver = $value->getResolver();
        $subscription = $this->directiveArgValue(
            $this->fieldDirective($value->getField(), $this->name()),
            'subscription'
        );

        if (! $subscription) {
            $message = sprintf(
                "The `websocket` directive requires a `subscription` argument. Missing on %s [%s]",
                $value->getNodeName(),
                $value->getFieldName()
            );

            throw new DirectiveException($message);
        }

        return $value->setResolver(function () use ($resolver, $subscription) {
            $resolved = call_user_func_array($resolver, func_get_args());

            Publisher::broadcast($subscription, $resolved);

            return $resolved;
        });
    }
}
