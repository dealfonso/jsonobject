<?php
// MIT License
//
// Copyright (c) 2023 Carlos de Alfonso (https://github.com/dealfonso)
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

/**
 * To use JsonObject one must subclass JsonObject and define the ATTRIBUTES constant.
 * 
 * The ATTRIBUTES constant is an associative array where the keys are the names of the attributes 
 *   and the values are the types of the attributes.
 * 
 * The types can be:
 *  - int
 *  - float
 *  - str
 *  - bool
 *  - list[type]
 *  - dict[type]
 *  - object (which must be a subclass of JsonObject)
 * 
 * The types list and dict must have a subtype, which is the type of the elements of the list or dict.
 * 
 * An example of a class that uses JsonObject:
 * 
 * class User extends JsonObject {
 *    const ATTRIBUTES = [
 *      'id' => 'int',
 *      'name' => 'str',
 *      'age' => 'int',
 *      'emails' => 'list[str]',
 *      'address?' => 'Address',
 *    ];
 * 
 *    public function isAdult() {
 *     return $this->age >= 18;
 *    }
 * }
 * 
 * class Address extends JsonObject {
 *   const ATTRIBUTES = [
 *    'street' => 'str',
 *    'number' => 'int',
 *    'city' => 'str',
 *    'country' => 'str',
 *   ];
 * }
 * 
 * It is possible to add inheritance of JsonObject. In this case, the ATTRIBUTES constant must be defined to add new attributes
 *  to the parent class. For example:
 * 
 * class UserWithPhone extends User {
 *   const ATTRIBUTES = [
 *      'phone' => 'str',
 *  ];
 * }
 * 
 * The class UserWithPhone will have all the attributes of User plus the attribute 'phone'.
 * 
 * When defining the name of the attributes, one can add a '?' at the end of the name to indicate that the field is optional.
 *   For example, the field 'address?' is optional.
 * 
 * The idea is to use JsonObject to parse json data into objects. So that these objects may contain other methods that will
 *   help to implement the data model of the application.
 * When the data is parsed, the content is recursively parsed into the types defined in the ATTRIBUTES constant. If the data is 
 *   not valid, because it does not contain the expected values, an exception is thrown. Each field is considered to be
 *   mandatory so that it must exist in the parsed object (or array). Moreover, the object must be of the type defined (i.e. it
 *   must be correctly parsed by the specific type). 
 * If one of the values is null, but the field is not defined as optional (i.e. it does not contain the trailing ? in the name),
 *   then an exception is thrown.
 * 
 * The JsonObject class has the following methods:
 * - __construct($data) - Creates a new object from the given data
 * - __get($name) - Returns the value of the field with the given name
 * - __set($name, $value) - Sets the value of the field with the given name
 * - __isset($name) - Returns true if the field with the given name is set
 * - toArray() - Returns an associative array with the data of the object
 * - toObject() - Returns an object with the data of the object
 * - toJson() - Returns a json string with the data of the object
 * - fromArray($data) - Creates a new object from the given associative array
 * - fromObject($data) - Creates a new object from the given object
 */

 /** TODO:
  *     - Enhance the usage of STRICT_TYPE_CHECKING
  *         - Check that the type of the value is the expected one when adding elements to JsonDict, JsonList and when setting the
  *           value of an attribute in JsonObject
  */

namespace ddn\jsonobject;

define('JSONOBJECT_VERSION', '0.1.0');

if (!defined('STRICT_TYPE_CHECKING')) {
    define('STRICT_TYPE_CHECKING', false);
}

if (!defined('JSONOBJECT_DEBUGGING')) {
    define('JSONOBJECT_DEBUGGING', false);
}

if (JSONOBJECT_DEBUGGING) {
    function __debug(...$args) {
        foreach ($args as $arg) {
            echo "<pre>";
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $trace = $trace[1];
            echo basename($trace['file']). " (" . ($trace['function']) . ")". ":" . $trace['line'] . " ";
            var_dump($arg);
            echo "</pre>";
        }
    }
} else {
    function __debug(...$args) {}
}

function check_array_is_list(mixed $arr) : bool {
    if ($arr === null) {
        return false;
    }
    if ($arr === []) {
        return true;
    }
    return array_keys($arr) === range(0, count($arr) - 1);
}

abstract class JsonBaseObject {
    const ATTRIBUTES = [];

    /**
     * Parses a value of a given type
     * @param $type string The type of the value
     * @param $value mixed The value to be parsed
     * @param $subtype string | null The type of the elements if type is list or dict
     */
    protected static function parse_typed_value($type, $value, $subtype = null) {
        if ($value === null) {
            return null;
        }
        // If type is a dict, then it is a dict of objects
        if (substr($type, 0, 5) == 'dict[' && $type[strlen($type)-1] == ']') {
            $subtype = substr($type, 5, strlen($type)-6);
            $type = 'dict';
        }
        if (substr($type, 0, 5) == 'list[' && $type[strlen($type)-1] == ']') {
            $subtype = substr($type, 5, strlen($type)-6);
            $type = 'list';
        }
        switch ($type) {
            case 'str':
                try {
                    return (string)$value;
                } catch (\Exception $e) {
                    throw new \Exception("Expected a string, but got '$value' (" . gettype($value) . ")");
                }
            case 'bool':
                return (bool)($value == 0 ? false : true);
            case 'int':
                if (is_numeric($value) && !is_string($value)) {
                    return intval($value);
                }
                throw new \Exception("Expected a number, but got '$value' (" . gettype($value) . ")");
            case 'float':
                if (is_numeric($value) && !is_string($value)) {
                    return floatval($value);
                }
                throw new \Exception("Expected a number, but got '$value' (" . gettype($value) . ")");
            case 'double':
                if (is_numeric($value) && !is_string($value)) {
                    return doubleval($value);
                }
                throw new \Exception("Expected a number, but got '$value' (" . gettype($value) . ")");
            case 'dict':
                // If the value is an array, use function fromArray else use function fromObject
                if (is_array($value)) {
                    return JsonDict::fromArray($subtype, $value);
                } else if (is_object($value)) {
                    return JsonDict::fromObject($subtype, $value);
                } else {
                    throw new \Exception("Expected a dict[$subtype], but got '$value' (" . gettype($value) . ")");
                }
            case 'list':
                if (!is_array($value) && !($value instanceof \Traversable)) {
                    throw new \Exception("Expected a list[$subtype], but got '$value' (" . gettype($value) . ")");
                }
                return JsonList::fromArray($subtype, $value);
            default:
                if (is_array($value)) {
                    return $type::fromArray($value);
                } else if (is_object($value)) {
                    return $type::fromObject($value);
                } else {
                    throw new \Exception("Value is not an object");
                }
        }
    }
    /**
     * Converts a value of a given type, making that if the value is an object, it is converted to an array using function toArray
     * @param $type string The type of the value
     * @param $value mixed The value to be parsed
     * @return mixed The converted value
     */
    protected static function convert_typed_value_array($type, $value) {
        return static::convert_typed_value($type, $value, "toArray");
    }
    /**
     * Converts a value of a given type, making that if the value is an object, it is converted to a stdObject using function toObject
     * @param $type string The type of the value
     * @param $value mixed The value to be parsed
     * @return mixed The converted value
     */
    protected static function convert_typed_value_object($type, $value) {
        return static::convert_typed_value($type, $value, "toObject");
    }
    /**
     * Converts a value of a given type, using the given function to generate the sub-objects of not simple types
     * @param $type string The type of the value
     * @param $value mixed The value to be parsed
     * @param $funcName string The name of the function to be used to generate the sub-objects
     * @return mixed The converted value
     */
    protected static function convert_typed_value($type, $value, $funcName = "toArray") {
        if ($value === null) {
            return null;
        }
        $subtype = null;
        // If type is a dict, then it is a dict of objects
        if (substr($type, 0, 5) == 'dict[' && $type[strlen($type)-1] == ']') {
            $subtype = substr($type, 5, strlen($type)-6);
            $type = 'dict';
        }
        if (substr($type, 0, 5) == 'list[' && $type[strlen($type)-1] == ']') {
            $subtype = substr($type, 5, strlen($type)-6);
            $type = 'list';
        }
        switch ($type) {
            case 'str':
                return $value;
            case 'bool':
                return (bool)$value;
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'dict':
            case 'list':
                return $value->$funcName();
            default:
                return $value->$funcName();
        }
    }
    /**
     * Obtains the default value for a given type
     * @param $type string The type of the value
     * @param $subtype string | null The type of the elements if type is list or dict
     */
    protected static function default_value($type, $subtype = null) {
        switch ($type) {
            case 'str':
                return '';
            case 'bool':
                return false;
            case 'int':
                return 0;
            case 'float':
                return 0.0;
            case 'dict':
                return JsonDict::fromArray($subtype, array());
            case 'list':
                return JsonList::fromArray($subtype, array());
            default:
                return $type::fromArray(array());
        }
    }
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
     */
    public function __toString() {
        return $this->toJson(true);
    }

    /** Converts the object to an object */
    abstract public function toObject() : \stdClass | array;

    /** Converts the object to an associative array */
    abstract public function toArray() : array;
}

class JsonDict extends JsonBaseObject implements \ArrayAccess, \IteratorAggregate, \Countable {
    /// The values in this dict
    protected $values = array();
    /// The type of the values in this dict
    protected $type = null;

    /** 
     * Retrieves the type of the values in this dict
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Makes that the object is countable (i.e. can be used in count())
     */
    public function count() : int {
        return count($this->values);
    }

    /**
     * Function that is used to validate that the offset has the proper type
     */
    protected function _validateOffset(mixed $offset): void {
        if (gettype($offset) != 'string') {
            throw new \TypeError(sprintf('Offset must be of type string, %s given', gettype($offset)));
        }
    }

    /**
     * Creates a new JsonDict from the given array
     */
    public function __construct(mixed $type) {
        $this->type = $type;
    }

    /**
     * Returns true if the given offset has a value
     */
    public function offsetExists(mixed $offset) : bool {
        $this->_validateOffset($offset);
        return array_key_exists($offset, $this->values);
    }

    /**
     * Returns the value at the given offset
     */
    public function offsetGet(mixed $offset) : mixed {
        $this->_validateOffset($offset);
        return $this->values[$offset];
    }

    /**
     * Sets the value at the given offset
     */
    public function offsetSet(mixed $offset, mixed $value) : void {
        $this->_validateOffset($offset);
        if ($offset === null) {
            $this->values[] = $value;
            return;
        }
        $this->values[$offset] = $value;
    }

    /**
     * Removes the value at the given offset
     */
    public function offsetUnset(mixed $offset) : void {
        $this->_validateOffset($offset);
        unset($this->values[$offset]);
    }

    /**
     * Returns an iterator for the dictionary (or the array)
     */
    public function getIterator() : \Traversable {
        return new \ArrayIterator($this->values);
    }

    /**
     * Creates a new JsonDict from the given array
     * @param $type string The type of the values in the dict
     * @param $array array The array to be converted
     */
    public static function fromArray($type, $array) {
        $class = get_called_class();
        $object = new $class($type);
        foreach ($array as $key => $value) {
            $object[$key] = self::parse_typed_value($type, $value);
        }
        return $object;
    }

    /**
     * Converts the dictionary to an associative array where the keys are the name of
     *   the properties and the values are the values of the properties
     */
    public function toArray() : array {
        $array = array();
        foreach ($this->values as $key => $value) {
            $array[$key] = self::convert_typed_value_array($this->type, $value);
        }
        return $array;
    }

    /**
     * Converts the dictionary to an object
     */
    public function toObject() : \stdClass | array {
        $array = array();
        foreach ($this->values as $key => $value) {
            $array[$key] = self::convert_typed_value_object($this->type, $value);
        }
        return (object)$array;
    }

    /**
     * Returns the keys of the dictionary
     */
    public function keys() : array {
        return array_keys($this->values);
    }

    /**
     * Returns the values of the dictionary
     */
    public function values() : array {
        return array_values($this->values);
    }
}

class JsonList extends JsonDict {
    /**
     * Validates that the offset is an integer
     * @param $offset mixed The offset to be validated
     */
    protected function _validateOffset(mixed $offset): void {
        if (!is_int($offset)) {
            throw new \TypeError("Array keys must be integers");
        }
    }

    /**
     * Converts the array to an object
     */
    public function toObject() : \stdClass | array {
        $type = $this->type;
        return [ ...array_map(function ($x) use ($type) { return self::convert_typed_value_object($type, $x); }, $this->values) ];
    }

    /**
     * Sets the value at the given offset, and also implements the case of appending to the array like $array[] = $value
     * @param $offset mixed The offset to be set
     * @param $value mixed The value to be set
     */
    public function offsetSet(mixed $offset, mixed $value) : void {
        // TODO: check that the value is of the proper type (or can be converted to the proper type) when using STRICT_TYPE_CHECKING
        if ($offset === null) {
            $offset = count($this->values);
        }
        parent::offsetSet($offset, $value);
    }
}

class JsonObject extends JsonBaseObject {
    // This array stores the definition for the attributes of the object
    const ATTRIBUTES=[];

    // This array stores the values of the attributes of the object, set in ATTRIBUTES
    private $_attributeValue = [];

    // This array stores the definition of the attributes for each class that descends from JsonObject. Specifically it stores
    //   whether the attributes are mandatory or not, the type, and subtype, and the default value (if set)
    private static $_attributeDefinition = [];

    public function __construct(...$args) {
        // PHP 8 allows for named arguments, so we need to check for that...
        //  - if we are using named arguments, we'll receive an array with the keys being the names of the arguments
        //  - if we are not using named arguments, we'll receive a list of arguments; if there is only one argument and it is an array, 
        //      we'll assume it is a list of arguments
        if (check_array_is_list($args)) {
            $resultingArgs = [];
            foreach ($args as $args2) {
                if (is_array($args2) && ! check_array_is_list($args2)) {
                    $resultingArgs = array_merge($resultingArgs, $args2);
                } else {
                    throw new \Exception("Invalid arguments for ".get_called_class()."; must be a list of arguments");
                }
            }
            $args = $resultingArgs;
        }
        __debug("Using arguments for ".get_called_class().": ".json_encode($args));

        // Let's check if this is the first time that we are creating an object of this class; if so, we need to
        //  initialize the definition for the attributes
        $firstTime = !isset(static::$_attributeDefinition[static::class]);
        
        if ($firstTime) {
            static::$_attributeDefinition[static::class] = [];

            // We are gathering all the attributes for the current class and all the parent classes
            //   to implement the inheritance of attributes by only defining the new attributes in the child class
            //   ATTRIBUTES constant
            $attributesForPrecedingClasses = [];
            $parentClass = get_called_class();
            while ($parentClass !== false) {
                if (!empty($parentClass::ATTRIBUTES)) {
                    array_unshift($attributesForPrecedingClasses, $parentClass::ATTRIBUTES);
                }
                $parentClass = get_parent_class($parentClass);
            }

            // Now we are detecting wether there are any attribute that shadows the definition of previous classes
            //  this is not allowed because it would be confusing. 
            //      E.g. class A { const ATTRIBUTES = ['a' => 'int']; }
            //           class B extends A { const ATTRIBUTES = ['a' => 'string']; }
            //           class C extends A { const ATTRIBUTES = ['a?' => 'bool']; }
            //  attribute "a" is shadowed both in class B and C, so it is not allowed and we need to check because
            //    of how we are going to define the attributes (i.e. the optional feature of attributes makes that
            //    we need to check for the name of the attribute without the optional indicator)
            $attributeNames = [];
            foreach ($attributesForPrecedingClasses as $attributesForPrecedingClass) {
                foreach (array_keys($attributesForPrecedingClass) as $attributeName) {
                    if ($attributeName[strlen($attributeName)-1] == '?') {
                        $attributeName = substr($attributeName, 0, strlen($attributeName)-1);
                    }
                    if (in_array($attributeName, $attributeNames)) {
                        throw new \Exception("Attribute $attributeName shadows the definition of previous classes in class ".get_called_class());
                    } else {
                        $attributeNames[] = $attributeName;
                    }
                }
            }

            // Now that we know that there is no shadow of attributes, we can merge all the attributes
            $attributesForClass = array_merge(...$attributesForPrecedingClasses);

            // Now we are building the attribute definition for this specific class
            foreach ($attributesForClass as $name => $type) {
                $defaultValue = null;
                if (is_array($type)) {
                    if (count($type) != 2) {
                        throw new \Exception("Invalid attribute definition for $name (".get_called_class()."); must be <type> or [ <type>, <default> ]");
                    }
                    $defaultValue = $type[1];
                    $type = $type[0];
                }

                $mandatory = true;
                if ($name[strlen($name)-1] == '?') {
                    $name = substr($name, 0, strlen($name)-1);
                    $mandatory = false;
                }

                $subtype = null;

                // If type is a dict, then it is a dict of objects
                if (substr($type, 0, 5) == 'dict[' && $type[strlen($type)-1] == ']') {
                    $subtype = substr($type, 5, strlen($type)-6);
                    $type = 'dict';
                }
                // If type is a list, then it is a list of objects
                if (substr($type, 0, 5) == 'list[' && $type[strlen($type)-1] == ']') {
                    $subtype = substr($type, 5, strlen($type)-6);
                    $type = 'list';
                }
                // Store the attribute definition
                static::$_attributeDefinition[static::class][$name] = [
                    'mandatory' => $mandatory,
                    'subtype' => $subtype,
                    'type' => $type,
                    'default' => $defaultValue
                ];
            }                
        }

        // Now we are checking if the attributes are initialized by using properties or if there is a default
        //  value for the attribute; in both cases, we'll set the value for the attribute
        foreach (static::$_attributeDefinition[static::class] as $name => $definition) {
            if (property_exists($this, $name)) {
                // We'll convert the value to the correct type
                $value = $this->$name;
                unset($this->$name);
                $this->$name = self::parse_typed_value($definition['type'], $value);
            } else {
                // If there is a default value, we'll use it
                if ($definition['default'] !== null) {
                    $defaultValue = $definition['default'];
                    if (is_callable($defaultValue)) {
                        $defaultValue = $defaultValue();
                    } else {
                        if (is_string($defaultValue)) {
                            if (isset($this->$defaultValue) && is_callable($this->$defaultValue)) {
                                $defaultValue = $this->$defaultValue();
                            }
                        }
                    }
                    $this->$name = self::parse_typed_value($definition['type'], $defaultValue);
                }
            }
        }

        // Now we'll set the values in the constructor
        foreach ($args as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    public function __get($name) {
        if (method_exists($this, $name)) {
            return [ $this, $name ];
        }
        if (!isset(static::$_attributeDefinition[static::class][$name])) {
            throw new \InvalidArgumentException("Unknown attribute $name in class " . get_called_class());
        }
        // If a value is requested, and it is not set, we'll set it to the default value
        if (!array_key_exists($name, $this->_attributeValue)) {
            if (static::$_attributeDefinition[static::class][$name]['mandatory']) {
                $this->_attributeValue[$name] = self::default_value(static::$_attributeDefinition[static::class][$name]['type']);
            } else {
                $this->_attributeValue[$name] = null;
            }
        }
        // Return the value for the attribute
        return $this->_attributeValue[$name];
    }

    public function __set($name, $value) {
        if (!isset(static::$_attributeDefinition[static::class][$name])) {
            throw new \InvalidArgumentException("Unknown attribute $name in class " . get_called_class());
        }
        if ($value === null) {
            $this->_attributeValue[$name] = null;
            return;
        }
        // TODO: better check that the value is compatible with the type (STRICT_TYPE_CHECKING)
        switch (static::$_attributeDefinition[static::class][$name]["type"]) {
            case 'int':
                if ((!STRICT_TYPE_CHECKING) && is_numeric($value)) {
                    $value = intval($value);
                }
                if (!is_int($value)) {
                    throw new \InvalidArgumentException("Attribute $name must be an int");
                }
                break;
            case 'float':
                if ((!STRICT_TYPE_CHECKING) && is_numeric($value)) {
                    $value = floatval($value);
                }
                if (!is_float($value) && !is_int($value)) {
                    throw new \InvalidArgumentException("Attribute $name must be a float");
                }
                $value = (float)$value;
                break;
            case 'str':
                if ((!STRICT_TYPE_CHECKING) && is_numeric($value) && (!is_string($value))) {
                    $value = strval($value);
                }
                if (!is_string($value)) {
                    throw new \InvalidArgumentException("Attribute $name must be a string");
                }
                break;
            case 'bool':
                $value = (bool)($value == 0 ? false : true);
                break;
            case 'list':
                if (!is_a($value, 'ddn\jsonobject\JsonList')) {
                    // If the value is not of the same type, we'll try to convert it
                    if (!is_array($value)) {
                        throw new \InvalidArgumentException("Attribute $name must be a list");
                    }
                    $value = JsonList::fromArray(static::$_attributeDefinition[static::class][$name]['subtype'], $value);
                } else {
                    if (($value->getType() != static::$_attributeDefinition[static::class][$name]['subtype']) && (count($value) > 0)) {
                        throw new \InvalidArgumentException("Attribute $name must be a list of type ".static::$_attributeDefinition[static::class][$name]['subtype']);
                    }
                }
                break;
            case 'dict':
                if (!is_a($value, 'ddn\jsonobject\JsonDict')) {
                    // If the value is not of the same type, we'll try to convert it
                    if (!is_array($value)) {
                        throw new \InvalidArgumentException("Attribute $name must be a dict");
                    }
                    $value = JsonDict::fromArray(static::$_attributeDefinition[static::class][$name]['subtype'], $value);
                } else {
                    if (($value->getType() != static::$_attributeDefinition[static::class][$name]['subtype']) && (count($value) > 0)) {
                        throw new \InvalidArgumentException("Attribute $name must be a dict of type ".static::$_attributeDefinition[static::class][$name]['subtype']);
                    }
                }
                break;
            default:
                if (!is_object($value)) {
                    throw new \InvalidArgumentException("Attribute $name must be an object");
                }
                if (!is_a($value, static::$_attributeDefinition[static::class][$name]["type"])) {
                    throw new \InvalidArgumentException("Attribute $name must be an object of type ".static::$_attributeDefinition[static::class][$name]["type"]);
                }
                break;
        }
        // Set the value of the attribute
        $this->_attributeValue[$name] = $value;
    }

    /**
     * Creates an object from an associative array where the keys are the name of the attributes and the values are
     *   the values of these attributes.
     * This function takes into account the definition of the parameters; so the values must take into account the type
     *   of the definition.
     * 
     * @param $array is the associative array from which to create the object
     * @param $strict is a boolean indicating if the function should check that every attribute in the array is defined for the object
     *                If the array contains an attribute that is not defined for the object, an exception is thrown
     * @return the object created from the array
     * 
     * @throws \InvalidArgumentException if the array contains an attribute that is not defined for the object
     * @throws \InvalidArgumentException if the array is missing a mandatory attribute
     */
    public static function fromArray(array $array, bool $strict = false) {
        if ($strict) {
            foreach ($array as $attribute => $value) {
                if (!isset(static::$_attributeDefinition[static::class][$attribute])) {
                    throw new \InvalidArgumentException("Unknown attribute $attribute in class " . get_called_class());
                }
            }
        }
        $class = get_called_class();
        $object = new $class();
        foreach (static::$_attributeDefinition[static::class] as $attribute => $definition) {
            if (!array_key_exists($attribute, $array)) {
                if ($definition['mandatory']) {
                    throw new \InvalidArgumentException("Missing mandatory attribute $attribute for class " . get_called_class() . ". Not found in " . json_encode($array));
                }
                continue;
            }
            if ($definition['subtype'] === null) {
                $object->$attribute = self::parse_typed_value($definition['type'], $array[$attribute]);
            } else {
                $object->$attribute = self::parse_typed_value($definition['type'], $array[$attribute], $definition['subtype']);
            }
        }
        return $object;
    }

    /**
     * Creates a JsonObject from an object, by converting all the attributes of the object into attributes of the JsonObject.
     * 
     */
    public static function fromObject($object, $strict = false) {
        return static::fromArray((array)$object, $strict);
    }

    /**
     * Converts the object into an associative array where the keys are the name of the attributes and the values are the
     *  values of these attributes.
     */
    public function toArray() : array {
        $array = array();
        foreach (static::$_attributeDefinition[static::class] as $attribute => $definition) {
            $array[$attribute] = self::convert_typed_value_array(static::$_attributeDefinition[static::class][$attribute]['type'], $this->$attribute);
        }
        return $array;
    }

    /**
     * Converts the object into an standard object where the attributes are the attributes of the object.
     */
    public function toObject() :  \stdClass | array {
        $obj = new \stdClass();
        foreach (static::$_attributeDefinition[static::class] as $attribute => $definition) {
            $obj->$attribute = self::convert_typed_value_object(static::$_attributeDefinition[static::class][$attribute]['type'], $this->$attribute);
        }
        return $obj;
    }
}
?>