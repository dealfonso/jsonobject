<?php

namespace ddn\typedobject;

class TypedDict extends BaseTypedObject implements \ArrayAccess, \IteratorAggregate, \Countable {
    /// The values in this dict
    protected $values = array();
    /// The type of the values in this dict
    protected TypeDefinition $type;

    /** 
     * Retrieves the type of the values in this dict
     * @return TypeDefinition The type of the values in this dict
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Makes that the object is countable (i.e. can be used in count())  (this is required to implement the ArrayAccess interface)
     * @return int The number of elements in the dict
     */
    public function count() : int {
        return count($this->values);
    }

    /**
     * Function that is used to validate that the offset has the proper type
     * @param $offset mixed The offset to be validated
     * @return string The offset, if it is valid
     */
    protected function _validateOffset(mixed $offset): string {
        if (is_numeric($offset)) {
            $offset = "" . $offset;
        }
        if (gettype($offset) != 'string') {
            throw new \TypeError(sprintf('Offset must be of type string, %s given', gettype($offset)));
        }
        return $offset;
    }

    /**
     * Creates a new TypedDict whose elements are from type $type
     * @param $type string The type of the values in the dict
     */
    public function __construct(TypeDefinition|string $type) {
        if (is_string($type)) {
            $type = TypeDefinition::fromString($type);
        }
        $this->type = $type;
    }

    /**
     * Returns true if the given offset has a value in the dict  (this is required to implement the ArrayAccess interface)
     * @param $offset mixed The offset to be checked
     * @return bool True if the offset has a value in the dict
     */
    public function offsetExists(mixed $offset) : bool {
        return array_key_exists($this->_validateOffset($offset), $this->values);
    }

    /**
     * Returns the value at the given offset in the dict (this is required to implement the ArrayAccess interface)
     * @param $offset mixed The offset to be retrieved
     * @return mixed The value at the given offset
     */
    public function offsetGet(mixed $offset) : mixed {
        return $this->values[$this->_validateOffset($offset)];
    }

    /**
     * Sets the value at the given offset in the dict (this is required to implement the ArrayAccess interface)
     * @param $offset mixed The offset to be set
     * @param $value mixed The value to be set
     */
    public function offsetSet(mixed $offset, mixed $value) : void {
        $value = $this->type->parse_value($value);
        if ($offset === null) {
            $this->values[] = $value;
            return;
        }
        $offset = $this->_validateOffset($offset);
        $this->values[$offset] = $value;
    }

    /**
     * Removes the value at the given offset in the dict (this is required to implement the ArrayAccess interface)
     * @param $offset mixed The offset to be removed
     */
    public function offsetUnset(mixed $offset) : void {
        unset($this->values[$this->_validateOffset($offset)]);
    }

    /**
     * Returns an iterator for the dictionary (or the array) (this is required to implement the IteratorAggregate interface)
     * @return \Traversable An iterator for the dictionary (or the array)
     */
    public function getIterator() : \Traversable {
        return new \ArrayIterator($this->values);
    }

    /**
     * Creates a new TypedDict from the given array
     * @param $type string The type of the values in the dict
     * @param $array array The array to be converted
     */
    public static function fromArray(TypeDefinition $type, $array) : TypedDict {
        // This is to enable the inheritance of the class (i.e. to enable TypedList::fromArray and create a TypedList object)
        $class = get_called_class();
        $object = new $class($type);
        foreach ($array as $key => $value) {
            $object[$key] = $type->parse_value($value);
        }
        return $object;
    }

    /**
     * Creates a TypedDict from an object, by converting all the attributes to key-value pairs in the dict
     * @param $type string The type of the values in the dict
     * @param $object \StdClass The object to be converted
     */
    public static function fromObject(TypeDefinition $type, \StdClass $object) {
        return static::fromArray($type, (array)$object);
    }

    /**
     * Converts the dictionary to an associative array where the keys are the name of
     *   the properties and the values are the values of the properties
     * @return array The dictionary as an associative array
     */
    public function toArray() : array {
        $array = array();
        foreach ($this->values as $key => $value) {
            $array[$key] = $this->type->convert_array($value);
        }
        return $array;
    }

    /**
     * Converts the dictionary to an object where the keys are the name of the properties
     *  and the values are the values of the properties
     * @return \stdClass The dictionary as an object
     */
    public function toObject() {
        $array = array();
        foreach ($this->values as $key => $value) {
            $array[$key] = $this->type->convert_object($value);
        }
        return (object)$array;
    }

    /**
     * Returns the keys of the dictionary (i.e. the names of the properties)
     * @return array The keys of the dictionary
     */
    public function keys() : array {
        return array_keys($this->values);
    }

    /**
     * Returns the values of the dictionary (i.e. the values of the properties)
     * @return array The values of the dictionary
     */
    public function values() : array {
        return array_values($this->values);
    }

    /**
     * Filters the dictionary according to the given callback
     * @param $callback callable The callback to be used to filter the dictionary.  The prototype of the
     *  callback is function($value, $key)(if it returns true, the element will be kept; otherwise, it 
     *  will be removed).
     * @return TypedDict The filtered dictionary
     */
    public function filter(callable $callback) : TypedDict {
        $class = get_called_class();
        $object = new $class($this->type);
        foreach ($this->values as $key => $value) {
            if ($callback($value, $key)) {
                $object[$key] = $value;
            }
        }
        return $object;
    }
}