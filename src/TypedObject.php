<?php
namespace ddn\typedobject;

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
 * TypedObject
 * 
 * TypedObject is a class that helps to parse json data into objects by defining the attributes of 
 *  the object and the types of the attributes. Then the data is parsed into the object, and the
 *  attributes are set with the values of the data.
 * 
 * To use TypedObject one must subclass TypedObject and define the ATTRIBUTES constant.
 * 
 * The ATTRIBUTES constant is an associative array where the keys are the names of the attributes 
 *   and the values are the types of the attributes.
 * 
 * The types can be:
 *  - int
 *  - float
 *  - string
 *  - bool
 *  - list[type]
 *  - dict[type]
 *  - object (which must be a subclass of TypedObject)
 * 
 * The types list and dict must have a subtype, which is the type of the elements of the list or dict, and
 *   be either a simple type or a subclass of TypedObject.
 * 
 * Each attribute can be defined nullable or not. To define the attribute as nullable, one must add
 *   a '?' at the beginning of the type. For example, 'age' => 'int' defines the attribute 'age' as not nullable,
 *   while 'age' => '?int' defines the attribute 'age' as nullable.
 * 
 * A nullable attribute can be set to null in the JSON object used to build the TypedObject, or the object can
 *   even not contain the attribute. In this case, the attribute will be set to null.
 * 
 * An example of a class that uses TypedObject:
 * 
 * class User extends TypedObject {
 *    const ATTRIBUTES = [
 *      'id' => 'int',
 *      'name' => 'string',
 *      'age' => 'int',
 *      'emails' => 'list[string]',
 *      'address' => '?Address',
 *    ];
 * 
 *    public function isAdult() {
 *     return $this->age >= 18;
 *    }
 * }
 * 
 * class Address extends TypedObject {
 *   const ATTRIBUTES = [
 *    'street' => 'string',
 *    'number' => 'int',
 *    'city' => 'string',
 *    'country' => 'string',
 *   ];
 * }
 * 
 * It is possible to add inheritance of TypedObject. In this case, the ATTRIBUTES constant must be defined to add new attributes
 *  to the parent class. For example:
 * 
 * class UserWithPhone extends User {
 *   const ATTRIBUTES = [
 *      'phone' => 'string',
 *  ];
 * }
 * 
 * The class UserWithPhone will have all the attributes of User plus the attribute 'phone'.
 * 
 * The idea is to use TypedObject to parse json data into objects. So that these objects may contain other methods that will
 *   help to implement the data model of the application.
 * 
 * When the data is parsed, the content is recursively parsed into the types defined in the ATTRIBUTES constant. If the data is 
 *   not valid, because it does not contain the expected values, an exception is thrown. Each attribute that is not nullable
 *   is considered to be mandatory so that it must exist in the parsed object (or array) or it may have a default value defined.
 * 
 * The default values can be either defined as an attribute defined in the object.
 * 
 * class User extends TypedObject {
 *   const ATTRIBUTES = [
 *    'id' => 'int',
 *    'name' => 'string'
 *   ];
 *   public $id = 0;        // Defines a default value for the attribute 'id'
 * }
 * 
 * Alternatively, the default value can be defined in the ATTRIBUTES constant as an array with two elements, the first one being the type
 *  and the second one being the default value. If the default value is a function name, it will be called to obtain the default value.
 * 
 * class User extends TypedObject {
 *  const ATTRIBUTES = [
 *   'id' => ['int', 0],    // Defines a default value for the attribute 'id'
 *   'name' => 'string',
 *   'age' => ['int', 'get_default_age'], // Defines a default value for the attribute 'age' using the function get_default_age
 *  ];
 *  public function get_default_age() {
 *   return 18;
 *  }
 * }
 * 
 * A complete example of a class that uses TypedObject:
 * 
 * --------------------------------
 * 
 * class User extends TypedObject {
 *  static function generate_id() {
 *      return rand(1, 1000);
 *  }
 *  const ATTRIBUTES = [
 *     'id' => [ 'int', "generate_id" ],
 *     'name' => 'string',
 *     'age' => 'int',
 *     'sex' => 'string'
 *  ];
 *  public $sex = "not revealed";
 * }
 * $json_string = '{"name": "John Doe", "age": 42}';
 * $user = User::fromObject(json_decode($json_string));
 * echo((string)$user);
 * 
 * This code will show an output similar to:
 * { "id": 187, "name": "John Doe", "age": 42, "sex": "not revealed" }
 * 
 * --------------------------------
 * 
 * The TypedObject class has the following methods:
 * - __construct($data) - Creates a new object from the given data
 * - __get($name) - Returns the value of the field with the given name
 * - __set($name, $value) - Sets the value of the field with the given name
 * - __isset($name) - Returns true if the field with the given name is set
 * - toArray() - Returns an associative array with the data of the object
 * - toObject() - Returns an object with the data of the object
 * - toJson() - Returns a json string with the data of the object
 * - fromArray($data) - Creates a new object from the given associative array
 * - fromObject($data) - Creates a new object from the given object
 * 
 * CONSTANTS:
 * 
 * - STRICT_TYPE_CHECKING - If set to true, the type of the value is checked when setting the value of an attribute
 * - STRICT_TYPE_CHECKING_EMPTY_ZERO - If set to true, an empty string is converted to 0 if the type is int, 0.0 if 
 *                          the type is float, and 'false' if the type is bool
 * - AUTOFILL_NOT_NULLABLES_WITH_DEFAULT_VALUES - If set to true, the default value is used for nullable attributes if 
 *                          the value is not set
 * - USE_UNINITIALIZED_STATE - If set to true, the attributes that are not set by the constructor will be considered
 *                          as uninitialized and will raise an error if they are accessed before being set
 * - UNINITIALIZED_NULLABLE_IS_NULL - If set to true, if an attribute is not initialized, but it is nullable, when we
 *                          access it, it will return null. Otherwise, it will raise an exception.
 */

 /** TODO:
  *     - Add composition of types (e.g. 'int|string')
  *     - In general, observe https://php.watch/versions/7.4/typed-properties
  */

require_once("constants.php");
require_once("functions.php");
require_once("BaseTypedObject.php");
require_once("TypedDict.php");
require_once("TypedList.php");
require_once("TypeDefinition.php");

class TypedObject extends BaseTypedObject {
    // This array stores the definition for the attributes of the object
    const ATTRIBUTES=[];

    // This array stores the values of the attributes of the object, set in ATTRIBUTES
    private $_attributeValue = [];

    // This array stores the definition of the attributes for each class that descends from TypedObject. Specifically it stores
    //   whether the attributes are nullable or not, the type, and subtype, and the default value (if set)
    protected static $_attributeDefinition = [];

    /**
     * This function recognises the attributes of the class and stores them in the static variable $_attributeDefinition
     *   so that they can be used later to validate the attributes of the object. This function is called the first time
     *   that the class is instantiated, and it is called only once.
     * 
     * The attributes are defined in the constant ATTRIBUTES, which is an associative array where the keys are the names
     *  of the attributes and the values are the types of the attributes. The types of the attributes are defined as
     *  strings, where the string is the name of the type of the attribute. The types of the attributes can be:
     *      - int
     *      - float
     *      - string
     *      - bool
     *      - list[<type>]
     *      - dict[<type>]
     *      - <class_name, descending from TypedObject>
     * 
     * This function will create a static variable $_attributeDefinition, which is an associative array where the keys 
     *   are the name of the classes and the values are associative arrays where the keys are the names of the attributes
     *   and the values are instances of TypeDefinition that describe the type of the attribute.
     * 
     * $_attributeDefinition = [
     *     'class_name' => [
     *        'attribute_name' => TypeDefinition,
     *        ...
     *     ],
     *     ...
     * ]
     * 
     */
    protected static function _introspect_attributes() {
        // We are building the definition of attributes for the preceding classes, so that we know the definition for
        //  the attributes of the this class coming from the preceding classes
        $definition_of_attributes_for_preceding_classes = [];
        $parentClass = get_called_class();
        while ($parentClass !== false) {
            if (!empty(static::$_attributeDefinition[$parentClass])) {
                $definition_of_attributes_for_preceding_classes = array_merge(static::$_attributeDefinition[$parentClass], $definition_of_attributes_for_preceding_classes);
            }
            $parentClass = get_parent_class($parentClass);
        }

        // Now we are building the attribute definition for this specific class, starting with the definition of the preceding classes
        $definition_of_attributes = [ ...$definition_of_attributes_for_preceding_classes ];

        // Now we are building the attribute definition for this specific class
        foreach (static::ATTRIBUTES as $name => $type) {
            // Let's check a special case in which the attributes do not define the type of the attribute. e.g.
            //  const ATTRIBUTES = ['a' => 'int', 'b'];     ('b' does not define the type of the attribute)
            //  in that case, we'll assume that the type is 'mixed' (i.e. any type is allowed)
            if (is_int($name)) {
                $name = $type;
                $type = 'mixed';
            }

            $has_default = false;
            $default = null;

            if (!static::_is_valid_attribute_name($name)) {
                throw new \Exception("Invalid attribute name \"$name\" for class \"".get_called_class()."\"; must be a valid PHP variable name");
            }

            if (is_array($type)) {
                if (count($type) != 2) {
                    throw new \Exception("Invalid attribute definition for \"$name\" for class \"".get_called_class()."\"; must be <type> or [ <type>, <default> ]");
                }
                $default = $type[1];
                $has_default = true;
                $type = $type[0];
            }

            $type_definition = TypeDefinition::fromString($type);
            if ($has_default) {
                // This default is stored as a "blind" value. Maybe it is a method in the instance or it is a value, but
                //  we don't know at this point. We'll resolve it later, when we are going to instantiate the object
                $type_definition->default = $default;
            }

            // Now we are detecting wether there are any attribute that shadows the definition of previous classes
            //  this is not allowed because it would be confusing. 
            //      E.g. class A { const ATTRIBUTES = ['a' => 'int']; }
            //           class B extends A { const ATTRIBUTES = ['a' => 'string']; }
            //           class C extends A { const ATTRIBUTES = ['a?' => 'bool']; }
            //  attribute "a" is shadowed both in class B and C, so it is not allowed and we need to check because
            //    of how we are going to define the attributes (i.e. the optional feature of attributes makes that
            //    we need to check for the name of the attribute without the optional indicator)
            // 
            // The only case in which we allow shadowing is when the attribute only changes the default value of the 
            //  attribute. In any other case, we'll throw an exception
            //
            // (*) if the definition is the same, we'll not complain about it
            if (isset($definition_of_attributes_for_preceding_classes[$name])) {
                if (!$definition_of_attributes_for_preceding_classes[$name]->equals($type_definition)) {
                    throw new \Exception("Attribute $name shadows the definition of previous classes in class ".get_called_class());
                }
            }

            // Store the attribute definition
            $definition_of_attributes[$name] = $type_definition;
        }        

        static::$_attributeDefinition[static::class] = $definition_of_attributes;
    }

    /** 
     * Checks that the attribute name is valid (i.e. starts with a letter o underscore and continues with valid
     *  characters for a variable name)
     * @param string $name The name of the attribute
     * @return bool True if the attribute name is valid, false otherwise
     */
    public static function _is_valid_attribute_name($name) {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }

    public function __construct(...$args) {
        // PHP 8 allows for named arguments, so we need to check for that...
        //  - if we are using named arguments, we'll receive an array with the keys being the names of the arguments
        //  - if we are not using named arguments, we'll receive a list of arguments; if there is only one argument and it is an array, 
        //      we'll assume it is a list of arguments
        if (check_array_is_list($args)) {
            $resultingArgs = [];
            foreach ($args as $args2) {
                // It needs to be a dictionary or an empty array (i.e. []: which may be either a list or a dict)
                if (is_array($args2) && (! check_array_is_list($args2) || count($args2) == 0)) {
                    $resultingArgs = array_merge($resultingArgs, $args2);
                } else {
                    throw new \Exception("Invalid arguments for ".get_called_class()."; must be a list of arguments");
                }
            }
            $args = $resultingArgs;
        }

        // Let's check if this is the first time that we are creating an object of this class; if so, we need to
        //  initialize the definition for the attributes
        $firstTime = !isset(static::$_attributeDefinition[static::class]);
        
        if ($firstTime) {
            static::_introspect_attributes();
        }

        // Now we are checking if the attributes are initialized by using properties or if there is a default
        //  value for the attribute; in both cases, we'll set the value for the attribute
        foreach (static::$_attributeDefinition[static::class] as $name => $definition) {
            try {
                // If the property existed, we need to unset it but keep the value, just in case that the definition of the
                //  attribute changes it (it has more precedence). Moreover, the value will need to be converted to the proper
                //  type in a latter stage
                $had_property = false;
                $property_value = null;
                if (property_exists($this, $name)) {
                    try {
                        if (isset($this->$name) || ($this->$name === null && $definition->nullable)) {
                            $had_property = true;
                            $property_value = $this->$name;
                        }
                    } catch (\Error $e) {
                        // If we could not get the value of the property, it may be because it is a typed property and it is not initialized
                        //  yet. In that case, we'll unset the property and we'll set it later
                    }
                    unset($this->$name);
                }                

                if (isset($args[$name])) {
                    $this->$name = $definition->parse_value($args[$name]);
                } else {
                    // If the attribute has a default value in the definition, we'll set it (e.g. using a function evaluation or a static value)
                    try {
                        $this->$name = self::_get_default_value($this, $definition, $name);
                    } 
                    catch (\Exception $e) {
                        // If we could not find the default value, we'll check if the property had a value and if not, if the attribute
                        //  is not nullable, we'll throw an exception because we did not find a value for it
                        if ($had_property) {
                            $this->$name = $definition->parse_value($property_value);
                        } else {
                            if (USE_DEFAULT_VALUES) {
                                $this->$name = $definition->default_value();
                            } else {
                                if (USE_UNINITIALIZED_STATE) {
                                    // If we are using the uninitialized state, we are not going to set the value of the attribute
                                    //  and we are not going to throw an exception... we'll leave the attribute uninitialize as
                                    //  PHP 7.4 does (https://php.watch/versions/7.4/typed-properties)
                                } else {
                                    throw new \TypeError("not initialized and it has not a default value");
                                }
                            }
                        }
                    }
                }
            } catch (\TypeError $e) {
                // If the resulting value of the default value is not of the proper type, we'll throw an exception
                throw new \TypeError(sprintf("Invalid value for attribute %s::$name (%s)", get_called_class(), $e->getMessage()));
            }
        }
    }

    /**
     * Returns the default value for an attribute, using the definition of the attribute and calling the object's function
     *  if the default value is the name of a function for the object
     * @param $object TypedObject The object that contains the attribute
     * @param $definition array The definition of the attribute
     * @param $attribute_name string The name of the attribute
     * @return mixed The default value for the attribute
     * @throws \Exception If there is no default value for the attribute
     */
    static protected function _get_default_value($object, $definition, $attribute_name) {
        if (isset($object->$attribute_name)) {
            return $definition->parse_value($object->$attribute_name);
        }
        if (isset($definition->default)) {
            $defaultValue = $definition->default;

            // Maybe it is a function or an array [ object, method ]
            if (is_callable($defaultValue)) {
                $defaultValue = $defaultValue();
            } else {
                // Maybe it is a method in the object
                if (is_string($defaultValue)) {
                    if (method_exists($object, $defaultValue)) {
                        $defaultValue = $object->$defaultValue();
                    } else {
                        // Maybe it is a static method in the class
                        if (method_exists(get_called_class(), $defaultValue)) {
                            $defaultValue = get_called_class()::$defaultValue();
                        }
                    }
                }
            }
            return $definition->parse_value($defaultValue);
        }
        throw new \Exception("No default value for attribute $attribute_name in class ".get_called_class());
    }

    /**
     * Checks if the object has all the attributes initialized
     * @return bool True if all the attributes are initialized, false otherwise
     */
    public function is_initialized() : bool {
        foreach (static::$_attributeDefinition[static::class] as $name => $definition) {
            if (!isset($this->$name)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the attributes that are not initialized
     * @return array The attributes that are not initialized
     */
    public function get_uninitialized_attributes() : array {
        $result = [];
        foreach (static::$_attributeDefinition[static::class] as $name => $definition) {
            if (!isset($this->$name)) {
                $result[] = $name;
            }
        }
        return $result;
    }

    /**
     * Retrieves the value of an attribute
     * @param string $name The name of the attribute
     * @return mixed The value of the attribute
     * @throws \InvalidArgumentException If the attribute is not defined for the object
     * @throws \Error If the attribute is not set and we are using the uninitialized state
     */
    public function __get($name) {
        if (method_exists($this, $name)) {
            return [ $this, $name ];
        }
        if (!isset(static::$_attributeDefinition[static::class][$name])) {
            throw new \InvalidArgumentException("Unknown attribute $name in class " . get_called_class());
        }
        // If a value is requested, and it is not set, we'll raise an exception
        if (!array_key_exists($name, $this->_attributeValue)) {
            if (USE_DEFAULT_VALUES) {
                $this->_attributeValue[$name] = static::$_attributeDefinition[static::class][$name]->default_value();
            } else {
                if (USE_UNINITIALIZED_STATE) {
                    if (UNINITIALIZED_NULLABLE_IS_NULL && static::$_attributeDefinition[static::class][$name]->nullable) {
                        return null;
                    }
                    // If we are using the uninitialized state, this is an error
                    throw new \Error(sprintf("Attribute %s::$name must not be accessed before initialization", get_called_class()));
                } 
            }
        }
        // Return the value for the attribute
        return $this->_attributeValue[$name];
    }

    /**
     * Sets the value of an attribute
     * @param string $name The name of the attribute
     * @param mixed $value The value to be set
     * @throws \InvalidArgumentException If the attribute is not defined for the object
     * @throws \TypeError If the value is not of the proper type
     */
    public function __set($name, $value) {
        if (!isset(static::$_attributeDefinition[static::class][$name])) {
            throw new \InvalidArgumentException("Unknown attribute $name in class " . get_called_class());
        }
        try {
            $this->_attributeValue[$name] = static::$_attributeDefinition[static::class][$name]->parse_value($value);
        } catch (\TypeError $e) {
            throw new \TypeError(sprintf("Invalid value for attribute %s::$name (%s)", get_called_class(), $e->getMessage()));
        }
    }

    /**
     * Checks if an attribute is set
     * @param string $name The name of the attribute
     * @return bool True if the attribute is set, false otherwise
     * @throws \InvalidArgumentException If the attribute is not defined for the object
     */
    public function __isset($name) {
        if (method_exists($this, $name)) {
            return true;
        }
        if (!isset(static::$_attributeDefinition[static::class][$name])) {
            throw new \InvalidArgumentException("Unknown attribute $name in class " . get_called_class());
        }
        // Otherwise, we check if the attribute is set
        return array_key_exists($name, $this->_attributeValue);
    }

    /**
     * Unsets an attribute
     * @param string $name The name of the attribute
     * @throws \InvalidArgumentException If the attribute is not defined for the object
     */
    public function __unset($name) {
        if (!isset(static::$_attributeDefinition[static::class][$name])) {
            throw new \InvalidArgumentException("Unknown attribute $name in class " . get_called_class());
        }
        if (array_key_exists($name, $this->_attributeValue)) {
            unset($this->_attributeValue[$name]);
        }
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
     * @throws \InvalidArgumentException if the array is missing a not nullable attribute
     */
    public static function fromArray(array $array, bool $strict = false) {
        $class = get_called_class();
        $result = new $class($array);
        if ($strict) {
            foreach ($array as $attribute => $value) {
                if (!isset(static::$_attributeDefinition[static::class][$attribute])) {
                    throw new \InvalidArgumentException("Unknown attribute $attribute in class " . get_called_class());
                }
            }
        }
        return $result;
    }

    /**
     * Creates a TypedObject from an object, by converting all the attributes of the object into attributes of the TypedObject.
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
            $array[$attribute] = static::$_attributeDefinition[static::class][$attribute]->convert_array($this->$attribute);
        }
        return $array;
    }

    /**
     * Converts the object into an standard object where the attributes are the attributes of the object.
     */
    public function toObject() :  \stdClass {
        $obj = new \stdClass();
        foreach (static::$_attributeDefinition[static::class] as $attribute => $definition) {
            $obj->$attribute = static::$_attributeDefinition[static::class][$attribute]->convert_object($this->$attribute);
        }
        return $obj;
    }
}
?>