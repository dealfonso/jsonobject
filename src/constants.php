<?php
namespace ddn\typedobject;

require_once("functions.php");

/**
 * The version of the library
 */
define('TYPEDOBJECT_VERSION', '0.3.0');
ns_constant('TYPEDOBJECT_VERSION', '0.3.0');

/**
 * If STRICT_TYPE_CHECKING is set to true, we'll check that the type of the value is the expected one when setting the value of an attribute.
 *   If it is set to false, we'll try first to convert the value to the expected type prior to throwing an exception. That means that if (e.g.)
 *   an attribute "age" is defined as an int, but we set it to "42", it will be converted to 42 (i.e ->age = "42" will be the same to ->age = 42).
 *   In particular, we'll try the following conversions:
 *     - string to int
 *     - string to float
 *     - string to bool
 *     - int or float to bool
 *     - int or float to string
 */
// if (!defined('STRICT_TYPE_CHECKING')) {
//     define('STRICT_TYPE_CHECKING', true);
// }
ns_constant('STRICT_TYPE_CHECKING', true);

/**
 * When setting the STRICT_TYPE_CHECKING to false, some values are tried to be converted to the expected type (see STRICT_TYPE_CHECKING). If the 
 *   conversion is not possible, an exception will be thrown.
 * 
 * But setting the EXTENDED_TYPE_CONVERSION to true, the library will try to convert the value to the expected type in more cases:
 *      - if the expected type is an array of a certain type, we'll try to convert the value to that type and then create the array; 
 *      - if the expected type is a dict with a certain type, we'll try to convert the value to that type and then create the dict;
 */
// if (!defined('EXTENDED_TYPE_CONVERSION')) {
//     define('EXTENDED_TYPE_CONVERSION', false);
// }
ns_constant('EXTENDED_TYPE_CONVERSION', false);

/**
 * If EMPTY_IS_ZERO is set to true, if we set a value to an empty string '' will be converted to 0 if the type is int,
 *   0.0 if the type is float or 'false' if the type is bool
 */
// if (!defined('EMPTY_IS_ZERO')) {
//     define('EMPTY_IS_ZERO', true);
// }
ns_constant('EMPTY_IS_ZERO', true);

/**
 * If USE_DEFAULT_VALUES is set to true, the attributes that are not set by the constructor will be set to the default 
 *  value for their type
 */
// if (!defined('USE_DEFAULT_VALUES')) {
//     define('USE_DEFAULT_VALUES', false);
// }
ns_constant('USE_DEFAULT_VALUES', false);

/**
 * If USE_UNINITIALIZED_STATE is set to true, the attributes that are not set by the constructor will be considered 
 *   as uninitialized and will raise an error if they are accessed before being set (as of https://php.watch/versions/7.4/typed-properties)
 * If it is set to false, if an attribute is not set by the constructor and does not have a default value, it will 
 *   raise an exception to indicate that the attribute is not set.
 */
// if (!defined('USE_UNINITIALIZED_STATE')) {
//     define('USE_UNINITIALIZED_STATE', true);
// }
ns_constant('USE_UNINITIALIZED_STATE', true);

/**
 * If UNINITIALIZED_NULLABLE_IS_NULL is set to true, if an attribute is not initialized, but it is nullable, when we
 *  access it, it will return null. Otherwise, it will raise an exception.
 */
// if (!defined('UNINITIALIZED_NULLABLE_IS_NULL')) {
//     define('UNINITIALIZED_NULLABLE_IS_NULL', true);
// }
ns_constant('UNINITIALIZED_NULLABLE_IS_NULL', true);