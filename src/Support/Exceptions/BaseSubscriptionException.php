<?php

namespace Nuwave\Lighthouse\Subscriptions\Support\Exceptions;

use Exception;

class BaseSubscriptionException extends Exception
{
    /**
     * GraphQL Errors.
     *
     * @var array
     */
    protected $errors;

    /**
     * Set query errors.
     *
     * @param array $errors
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get query errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
