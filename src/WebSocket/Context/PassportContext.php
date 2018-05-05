<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket\Context;

use Laravel\Passport\Guards\TokenGuard;

class PassportContext extends SubscriberContext
{
    /**
     * Passport token repository.
     *
     * @var TokenGuard
     */
    protected $tokens;

    /**
     * Authorized user.
     *
     * @var mixed
     */
    public $user;

    /**
     * Create instance of passport context.
     *
     * @param TokenGuard $tokens
     */
    public function __construct(TokenGuard $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Build subscription context.
     *
     * @return void
     */
    public function build()
    {
        parent::build();

        $this->request->headers->set(
            'authorization',
            array_get($this->subscriber->initialPayload(), 'Authorization'),
            true
        );

        $this->user = $this->tokens->user($this->request);
    }
}
