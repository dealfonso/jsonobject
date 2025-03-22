<?php

namespace ddn\typedobject;

require_once("constants.php");

/**
 * This function detects if the strict types are enabled in the PHP configuration or by using the declare(strict_types=1) directive
 * @return bool True if the strict types are enabled, false otherwise
 * (*) how it works: if strict_types are enabled, the function will throw an exception when trying to set a string to an int; if not,
 *     it will try to convert the string to an int.
 */
function check_strict_types() {
    try {
        function f(int $a) { return $a; }
        f("1");
        return false;
    } catch (\TypeError $e) {
        return true;
    }
}

/**
 * Sets or retrieves a constant in the current namespace
 * 
 * - ns_constant('TYPEDOBJECT_VERSION', '0.3.0') will define the constant TYPEDOBJECT_VERSION with the value '0.3.0'
 * - ns_constant('TYPEDOBJECT_VERSION') will return the value of the constant TYPEDOBJECT_VERSION
 * 
 * @param string $name The name of the constant
 * @param mixed ...$args If only one argument is passed, the function will return the value of the constant. If two arguments are passed, the function will set the value of the constant to the second argument
 * @return mixed The value of the constant
 */
function ns_constant(string $name, mixed ...$args): mixed {
    if (count($args) > 1) {
        throw new \Error("invalid use of ns_constant");
    }
    $name = __NAMESPACE__ . '\\' . $name;

    if (count($args) == 1) {
        $value = $args[0];
        if (!defined($name)) {
            define($name, $value);
        }
        return $value;
    } else {
        if (!defined($name)) {
            throw new \Error("The constant $name is not defined");
        }
        return constant($name);
    }
}

/**
 * Checks if the given array is a list (i.e. the keys are 0, 1, 2, ..., n-1)
 * @param mixed $arr The array to be checked
 * @return bool True if the array is a list, false otherwise
 */
function check_array_is_list(mixed $arr) : bool {
    if ($arr === null) {
        return false;
    }
    if ($arr === []) {
        return true;
    }
    return array_keys($arr) === range(0, count($arr) - 1);
}