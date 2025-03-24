<?php

namespace ddn\typedobject;

require_once("functions.php");

class TypeDefinition {
    public string $type;
    public bool $nullable;
    public ?TypeDefinition $subtype;
    public mixed $default;

    /**
     * Creates a new TypeDefinition object. It is protected because it is not intended to be used directly, but to be used
     *  by means of the ::fromString factory method
     * @param $args array The arguments to create the object
     * (*) the arguments are:
     *   - type string The type of the value
     *   - nullable bool Whether the value can be null
     *   - subtype string | null The type of the elements if type is list or dict
     *   - default mixed The default value of the value
     */
    protected function __construct(...$args) {
        if (check_array_is_list($args)) {
            $resultingArgs = [];
            foreach ($args as $args2) {
                // It needs to be a dictionary or an empty array (i.e. []: which may be either a list or a dict)
                if (is_array($args2) && (! check_array_is_list($args2) || count($args2) == 0)) {
                    $resultingArgs = array_merge($resultingArgs, $args2);
                }
            }
            $args = $resultingArgs;
        }
        foreach ([ 'type', 'nullable', 'subtype', 'default' ] as $key) {
            if (array_key_exists($key, $args)) {
                $this->$key = $args[$key];
            }
        }
    }

    public static function fromType(string $type, bool $nullable = false, ?TypeDefinition $subtype = null) : TypeDefinition {
        switch ($type) {
            case 'mixed':
                // This is a special case because (per definition) the mixed type is nullable
                $nullable = true;
                break;
            case 'string':
            case 'bool':
            case 'int':
            case 'float':
            case 'dict':
            case 'list':
            case 'array':
                break;
            default:
                if (!class_exists($type)) {
                    throw new \TypeError(sprintf("undefined class \"%s\"", $type));
                }
                break;
        }

        return new TypeDefinition([
            'type' => $type,
            'nullable' => $nullable,
            'subtype' => $subtype,
        ]);
    }

    /**
     * Creates a new TypeDefinition object from a string that defines the type (e.g. 'int', 'list[int]', 'dict[?string]')
     * @param $type string The type to be converted
     * @return TypeDefinition The object that represents the type
     */
    public static function fromString($type) : TypeDefinition {
        $type = trim($type);

        $nullable = false;
        if ($type[0] == '?') {
            $nullable = true;
            $type = substr($type, 1);
        }

        if ($type == 'mixed') {
            if ($nullable) {
                throw new \TypeError("mixed type cannot be nullable");
            }
        }

        $subtype = null;

        // If type is a dict or a list without subtype, then it is a dict or a list of mixed
        if ($type == 'list' || $type == 'dict') {
            $subtype = 'mixed';
        } else {
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
        }

        if ($subtype !== null) {
            $subtype = self::fromString($subtype);
        }

        switch ($type) {
            case 'array':
                throw new \Error("array type is only valid for specific library purposes");
                break;
        }

        return TypeDefinition::fromType($type, $nullable, $subtype);
    }

    /**
     * Compares the current object with another object and returns whether they are equal
     * @param $other TypeDefinition The object to compare with
     * @param $consider_default bool Whether to take into account the default value
     * @return bool Whether the objects are equal
     */
    public function equals(?TypeDefinition $other, bool $consider_default = false) : bool {
        if ($other === null) {
            return false;
        }
        if ($this->type !== $other->type) {
            return false;
        }
        if ($this->nullable !== $other->nullable) {
            return false;
        }
        if ($this->subtype === null) {
            if ($other->subtype !== null) {
                return false;
            }
        } else {
            if (!$this->subtype->equals($other->subtype, $consider_default)) {
                return false;
            }
        }
        if ($consider_default) {
            if (isset($this->default) !== isset($other->default)) {
                return false;
            }
            if (isset($this->default) && $this->default !== $other->default) {
                return false;
            }
        }
        // We are not taking into account the default value
        return true;
    }

    /**
     * Obtains the default value for a given type
     * @param $type string The type of the value
     * @param $subtype string | null The type of the elements if type is list or dict
     */
    public function default_value() {
        // The default value for a nullable is null
        if ($this->nullable) {
            return null;
        }
        switch ($this->type) {
            case 'mixed':
                return null;
            case 'string':
                return '';
            case 'bool':
                return false;
            case 'int':
                return 0;
            case 'float':
                return 0.0;
            case 'dict':
                return TypedDict::fromArray($subtype, array());
            case 'list':
                return TypedList::fromArray($subtype, array());
            case 'array':
                return array();
            default:
                return $this->type::fromArray(array());
        }
    }

    /**
     * Parses a value of a given value to a value that is of the type defined by this object (if possible)
     * @param $value mixed The value to be parsed
     * @return mixed The parsed value
     * @throws \TypeError If the value cannot be parsed
     */
    public function parse_value(mixed $value) : mixed {
        if ($value === null) {
            if (!$this->nullable) {
                throw new \TypeError("not nullable");
            }
            return null;
        }
        // This is to enable strict type checking 
        switch ($this->type) {
            case 'mixed':
                if (is_array($value) || ($value instanceof \Traversable)) {
                    $value = TypedList::fromArray(TypeDefinition::fromString("mixed"), $value);
                } else if (is_object($value) || ($value instanceof \stdClass)) {
                    $value = TypedDict::fromObject(TypeDefinition::fromString("mixed"), $value);
                }
                break;
            case 'int':
                if (!STRICT_TYPE_CHECKING) {
                    if (is_numeric($value)) {
                        $value = intval($value);
                    }
                    if (is_bool($value)) {
                        $value = $value ? 1 : 0;
                    }
                    if (EMPTY_IS_ZERO && ($value === "")) {
                        $value = 0;
                    }
                }
                if (!is_int($value)) {
                    throw new \TypeError(sprintf("expected an int, but received \"%s\" (%s)", $value, gettype($value)));
                }
                break;
            case 'float':
                if (!STRICT_TYPE_CHECKING) {
                    if (is_numeric($value)) {
                        $value = floatval($value);
                    }
                    if (is_bool($value)) {
                        $value = $value ? 1.0 : 0.0;
                    }
                    if (EMPTY_IS_ZERO && ($value === "")) {
                        $value = 0;
                    }
                }
                if (!is_float($value) && !is_int($value)) {
                    throw new \TypeError(sprintf("expected a float, but received \"%s\" (%s)", $value, gettype($value)));
                }
                $value = (float)$value;
                break;
            case 'string':
                if (!STRICT_TYPE_CHECKING) {
                    if (is_numeric($value) && (!is_string($value))) {
                        $value = strval($value);
                    }
                    if (is_bool($value)) {
                        $value = $value ? "true" : "false";
                    }
                }
                if (!is_string($value)) {
                    throw new \TypeError(sprintf("expected a string, but received \"%s\" (%s)", $value, gettype($value)));
                }
                break;
            case 'bool':
                if (!STRICT_TYPE_CHECKING) {
                    if (is_numeric($value)) {
                        $value = intval($value)==0 ? false : true;
                    }
                    if (EMPTY_IS_ZERO && ($value === "")) {
                        $value = false;
                    }
                    if (is_string($value)) {
                        $value = strtolower($value);
                        if ($value == "true") {
                            $value = true;
                        } else if ($value == "false") {
                            $value = false;
                        }
                    }
                }
                if (!is_bool($value)) {
                    throw new \TypeError(sprintf("expected a bool, but received \"%s\" (%s)", $value, gettype($value)));
                }
                break;
            case 'array':
                if (is_a($value, 'ddn\typedobject\TypedList')) {
                    $value = $value->toObject();
                } else {
                    if (!STRICT_TYPE_CHECKING && EXTENDED_TYPE_CONVERSION) {
                        try {
                            $value = [ $this->subtype->parse_value($value) ];
                        } catch (\TypeError $e) {
                            // We'll throw the exception below
                        }
                    }
                }
                if (!is_array($value)) {
                    throw new \TypeError(sprintf("expected an array, but received \"%s\" (%s)", $value, gettype($value)));
                }
                break;
            case 'list':
                if (!is_a($value, 'ddn\typedobject\TypedList')) {
                    // If the value is not of the same type, we'll try to convert it
                    if (!is_array($value) && !($value instanceof \Traversable)) {
                        throw new \TypeError(sprintf("expected a list[%s], but received \"%s\" (%s)", $this->subtype, $value, gettype($value)));
                    } else {
                        if (!STRICT_TYPE_CHECKING && EXTENDED_TYPE_CONVERSION) {
                            try {
                                $value = [ $this->subtype->parse_value($value) ];
                            } catch (\TypeError $e) {
                                // We'll throw the exception below
                            }
                        }
                    }
                    $value = TypedList::fromArray($this->subtype, $value);
                } else {
                    if (!$value->getType()->equals($this->subtype)) {
                        throw new \TypeError(sprintf("expected a list[%s], but received \"%s\" (%s)", $this->subtype, $value, gettype($value)));
                    }
                }
                break;
            case 'dict':
                if (!is_a($value, 'ddn\typedobject\TypedDict')) {
                    // If the value is not of the same type, we'll try to convert it
                    if (is_array($value) || ($value instanceof \Traversable)) {
                        $value = TypedDict::fromArray($this->subtype, $value);
                    } else if (is_object($value) || ($value instanceof \stdClass)) {
                        $value = TypedDict::fromObject($this->subtype, $value);
                    } else {
                        $throw = true;
                        if (!STRICT_TYPE_CHECKING && EXTENDED_TYPE_CONVERSION) {
                            try {
                                $value = TypedDict::fromArray($this->subtype, [ $value ]);
                                $throw = false;
                            } catch (\TypeError $e) {
                                // We'll throw the exception below
                            }
                        }
                        if ($throw) {
                            throw new \TypeError(sprintf("expected a dict[%s], but received \"%s\" (%s)", $this->subtype, $value, gettype($value)));
                        }
                    }
                } else {
                    if (!$value->getType()->equals($this->subtype)) {
                        throw new \TypeError(sprintf("expected a dict[%s], but received \"%s\" (%s)", $this->subtype, $value, gettype($value)));
                    }
                }
                break;
            default:
                if (is_a($value, $this->type)) {
                } else if (is_array($value) || ($value instanceof \Traversable)) {
                    $value = $this->type::fromArray($value);
                } else if (is_object($value) || ($value instanceof \stdClass)) {
                    $value = $this->type::fromObject($value, true);
                } else {
                    throw new \TypeError(sprintf("expected an object of type %s, but received \"%s\" (%s)", $this->type, $value, gettype($value)));
                }
                break;
        }
        return $value;
    }

    /**
     * Converts to string
     * @return string The string representation of the object
     */
    public function __toString() : string {
        $result = $this->type;
        if ($this->nullable && $this->type != 'mixed') {
            $result = '?' . $result;
        }
        if ($this->subtype !== null) {
            $result .= '[' . $this->subtype->type . ']';
        }
        return $result;
    }

    /**
     * This function converts a value that is in an arbitrary format to the format that is expected by the type to be
     *   returned to the user.
     * @param $value mixed The value to be converted
     * @return mixed The converted value
     */
    public function convert_value($value) {
        if ($value === null) {
            return null;
        }
        
        $type = $this->type;

        // If type is a dict, then it is a dict of objects, and if it is a list, then it is a list of objects. Anyway
        //   this is only needed to check if we need to use the function to convert the object to an array or to an object
        // (*) we are not checking the subtype here, as it will be used _inside_ the function to generate the sub-objects
        if (substr($type, 0, 5) == 'dict[' && $type[strlen($type)-1] == ']') {
            $type = 'dict';
        }
        if (substr($type, 0, 5) == 'list[' && $type[strlen($type)-1] == ']') {
            $type = 'list';
        }
        switch ($type) {
            case 'mixed':
                return $value;
            case 'string':
                return "" . $value;
            case 'bool':
                return (bool)$value;
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'dict':
            case 'list':
                return $value->toObject();
            case 'array':
                return $value;
            default:
                return $value->toObject();
        }
    }
}