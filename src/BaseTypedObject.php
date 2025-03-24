<?php

namespace ddn\typedobject;

use ddn\typedobject\TypeDefinition;

abstract class BaseTypedObject {
    const ATTRIBUTES = [];

    /**
     * Converts the current object to a json string
     * @param $pretty bool Whether to pretty print the json
     */
    public function toJson(bool $pretty = false) : string {
        if ($pretty) {
            return json_encode($this->toObject(), JSON_PRETTY_PRINT);
        } else {
            return json_encode($this->toObject());
        }
    }

    /**
     * Converts the current object to a string
     * @return string The string representation of the object
     */
    public function __toString() : string {
        return $this->toJson(true);
    }

    /** Converts the object to an object */
    abstract public function toObject();
}