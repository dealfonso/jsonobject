<?php

namespace ddn\typedobject;

require_once("constants.php");
require_once("functions.php");
require_once("TypedDict.php");

class TypedList extends TypedDict {
    /**
     * Validates that the offset is an integer
     * @param $offset mixed The offset to be validated
     */
    protected function _validateOffset(mixed $offset): string {
        if (!is_int($offset)) {
            throw new \TypeError("Array keys must be integers");
        }
        if ($offset < 0) {
            $offset = count($this->values) + $offset;
        }
        if ($offset < 0) {
            throw new \TypeError("Array keys must be non-negative integers");
        }
        if ($offset >= count($this->values)) {
            throw new \TypeError("Index out of bounds");
        }
        return $offset;
    }

    /**
     * Converts the array to an object
     * @return \stdClass The object representation of the array
     */
    public function toObject() : \stdClass {
        $type = $this->type;
        return (object) [ ...array_map(function ($x) use ($type) { 
            return $type->convert_object($x); 
        }, $this->values) ];
    }

    /**
     * Appends the given values to the list
     * @param $value mixed The values to be appended
     */
    public function append(mixed ...$value) : void {
        foreach ($value as $v) {
            $this[] = $v;
        }
    }

    /**
     * Removes the last element of the list
     * @return mixed The removed element
     * @throws \TypeError If the list is empty
     */
    public function pop() : mixed {
        if (count($this->values) == 0) {
            throw new \TypeError("List is empty");
        }
        $value = $this[-1];
        unset($this[-1]);
        return $value;
    }

    /**
     * Prepends the given values to the list
     * @param $value mixed The values to be prepended
     */
    public function unshift(mixed ...$value) : void {
        foreach ($value as $v) {
            $v = $this->type->parse_value($v);
            array_unshift($this->values, $v);
        }
    }

    /**
     * Removes the first element of the list
     * @return mixed The removed element
     * @throws \TypeError If the list is empty
     */
    public function shift() : mixed {
        if (count($this->values) == 0) {
            throw new \TypeError("List is empty");
        }
        $value = $this[0];
        unset($this[0]);
        return $value;
    }

    /**
     * Returns a slice of the list
     * @param $offset int The offset of the slice
     * @param $length ?int The length of the slice (if null, the slice will go until the end of the list)
     * @return TypedList The sliced list
     */
    public function slice(int $offset, ?int $length = null) : TypedList {
        $class = get_called_class();
        $object = new $class($this->type);
        $object->values = array_slice($this->values, $offset, $length);
        return $object;
    }

    /**
     * Returns the first element of the list
     * @return mixed The first element of the list
     */
    public function first() : mixed {
        if (count($this->values) == 0) {
            throw new \TypeError("List is empty");
        }
        return $this[0];
    }

    /**
     * Returns the last element of the list
     * @return mixed The last element of the list
     */
    public function last() : mixed {
        if (count($this->values) == 0) {
            throw new \TypeError("List is empty");
        }
        return $this[-1];
    }

    /**
     * Filters the list, according to the given callback
     * @param $callback callable The callback to be used to filter the list (if it returns true, the element 
     *  will be kept; otherwise, it will be removed)
     * @return TypedList The filtered list
     */
    public function filter(callable $callback) : TypedList {
        $class = get_called_class();
        $object = new $class($this->type);
        foreach ($this->values as $key => $value) {
            if ($callback($value, $key)) {
                $object[] = $value;
            }
        }
        return $object;
    }

    /**
     * Sorts the list according to the given callback
     * @param $callback ?callable The callback to be used to sort the list (if null, the list will be sorted
     *  according to the natural order of the elements)
     * @return TypedList The sorted list
     */
    public function sort(?callable $callback = null) : TypedList {
        $class = get_called_class();
        $object = new $class($this->type);
        $object->values = $this->values;
        if ($callback === null) {
            sort($object->values);
        } else {
            usort($object->values, $callback);
        }
        return $object;
    }
}