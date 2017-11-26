<?php

namespace Camuthig\Graphql\ServiceGenerator\Client;

/**
 * An interface to encapsulate custom defined Scalar values from the schema. We need some way for users to be able to
 * define their own implementation of serialization/deserialization for these things at some point.
 */
interface CustomScalarInterface
{
    /**
     * Serialize the client format into the correct format for the server.
     *
     * @return mixed
     */
    public function serialize();

    /**
     * Parse the value returned from the server into the correct client format.
     *
     * @param $value
     *
     * @return mixed
     */
    public static function parse($value);

    /**
     * Get the value of the custom scalar
     *
     * @return mixed
     */
    public function get();
}
