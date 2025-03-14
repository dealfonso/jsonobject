# `JsonObject`

`JsonObject` is a PHP class to ease the usage of objects coming from a JSON definition. The idea comes from using `pydantic` in python, and its ability to parse and validate json data into objects.

## Why `JsonObject`

I had to use an API from PHP, and that API returned me JSONObjects. So I needed to parse them into PHP objects that I was able to use in the app.

The workflow is
1. retrieve a JSON object definition
1. use `JsonObject` to parse the JSON definition
1. use the resulting objects in the application

## Use case

Let's take the following JSON example:

```json
{
    "id": 0,
    "name": "John Doe",
    "age": 42,
    "emails": [
        "my@email.com",
        "other@email.com"
    ],
    "address": {
        "street": "My street",
        "number": 42,
        "city": "My city",
        "country": "My country"
    }
}
```

Using `JsonObject`, I will be able to define my data model using the following classes:

```php
class User extends JsonObject {
    const ATTRIBUTES = [
        'id' => 'int',
        'name' => 'string',
        'age' => 'int',
        'emails' => 'list[string]',
        'address?' => 'Address',
    ];
}

class Address extends JsonObject {
    const ATTRIBUTES = [
        'street' => 'string',
        'number' => 'int',
        'city' => 'string',
        'country' => 'string',
    ];
}
```

And then add the following command:

```php
$user = User::fromObject(json_decode($json_text_definition));
```

The `JsonObject` class will carry out with parsing the content into objects, and we would be able to use its attributes as defined:

```php
echo($user->name);
```

The classes defined can also have methods that will make it easier to implement the application's data model. E.g. it would possible to define the class `User` like this:

```php
class User extends JsonObject {
    const ATTRIBUTES = [
        'id' => 'int',
        'name' => 'string',
        'age' => 'int',
        'emails' => 'list[string]',
        'address?' => 'Address',
    ];
    public function isAdult() {
        return $this->age >= 18;
    }
}
```

## Using `JsonObject`

The idea of the `JsonObject` class is to use it to parse _json_ data into objects. So that these objects may contain other methods that will help to implement the data model of the application.

When the json object (or array) is parsed, its content is recursively parsed according to the types defined in the `ATTRIBUTES` constant. If the data is not valid, because it does not contain the expected values, an exception is thrown. 

To use JsonObject one must subclass `JsonObject` and define the `ATTRIBUTES` constant for that class so that it defines the attributes expected for the objects of that class, along with the type of each one.
 
### Defining the types for the attributes

The `ATTRIBUTES` constant is an associative array where the keys are the _name for each attribute_,  and the values are the _type for each attribute_.
 
The possible types can be:
- int: int number
- float: floating point number
- string: string
- bool: boolean
- list[type]: list of objects of type _type_.
- dict[type]: dictionary of objects of type _type_. The keys for each entry of the dictionary is converted to strings.
- object: is a class name which must be a subclass of `JsonObject`.

### Optional and mandatory attributes

When defining the name of the attributes, one can add a `?` at the end of the name to indicate that the attribute is optional. For example, the attribute name `address?` in the use-case section is optional.
 
Each field is considered to be mandatory so that it must exist in the parsed object (or array). Moreover, the object must be of the type defined (i.e. it must be correctly parsed by the specific type). 

### Mandatory attributes

Any attribute that is not optional is considered to be madatory. This is of special interest in two points:

1. when creating the object from an external structure (either using `fromArray` or `fromObject` functions).
1. when generating the _object_ or _array_ representation of the `JsonObject`

When creating the object from an external structure, the `JsonObject` will take care of every mandatory field. And if any of them is missing, an exception will raise.

In the next example, an exception will raise because the mandatory field _age_ is not provided.

```php
class User extends JsonObject {
    const ATTRIBUTES = [
        "name" => "string",
        "age" => "int",
    ];
}
(...)
$user = User::fromArray([ "name" => "John" ]);
```

When converting the object to an array or to an object (or getting its json representation), a mandatory field will get a default value, even if not set.

So in the next example

```php
class User extends JsonObject {
    const ATTRIBUTES = [
        "name" => "string",
        "age" => "int",
        "birthDate?" => "string"
    ];
}
$user = new User();
echo((string)$user);
```

The output will be 

```json
{
    "name": "",
    "age": 0
}
```

Because while attributes _name_ and _age_ are mandatory and they get their default values (i.e. 0 for numbers, empty for strings, lists or dicts), attribute _birthDate_ is not mandatory and it has not been set, yet. So it is not generated in the output.

#### Setting to `null` on mandatory attributes

The problem of setting values to _null_ is of special relevance when considering whether an attribute is optional or not. 

One may think that, if we set a value to _null_ it would mean to _unset_ the value and so it should only be possible for optional values but not for mandatory values.

In `JsonObject` we have a different concept, because setting a property to _null_ will mean "setting a value to _null_" and not unsetting the property. In order to _unset_ the property, we should use function unset or somethink like that.

#### Unsetting mandatory attributes

`JsonObject` also enables to _unset_ values. For an optional attribute, it means _removing the value_ and thus it will not have any value in an array representation or an object (if retrieving the value, it will be set to _null_).

But for a mandatory attribute, unsetting it will mean _resetting its value to the default_. That means that it will be initialized to the default value of the type (i.e. 0 for numbers, empty for lists, strings or dicts, etc.) or its default value in the `ATTRIBUTES` constant.
 
### Inheritance

`JsonObject`s are also able to inherit attributes from their parent classes. Take the following example:

```php
class Vehicle extends JsonObject {
    const ATTRIBUTES = [
        "brand" => "string",
        "color" => "string"
    ]
}
class Car extends Vehicle {
    const ATTRIBUTES = [
        "wheels" => "int"
    ]
}
class Boat extends Vehicle {
    const ATTRIBUTES = [
        "length" => "float"
    ]
}
```

In this example, class `Vehicle` will only have attribute _brand_ and _color_, but class `Car` will have _brand_, _color_ and _wheels_ attributes, while class `Boat` will have _brand_, _color_ and _length_ attributes.

### Creation of objects

Objects from children classes of `JsonObject` can be created using the static method `::fromArray` or `::fromObject`, starting from a _json_ parsed object.

In the previous example, if we have a file _car.json_ with the following content: 

```json
{
    "brand": "BMW",
    "color": "black"
}
```

We can use the following code to get an instance of the `Vehicle` class:

```php
$json = file_get_contents("car.json");
$vehicle = Vehicle::fromArray((array)json_decode($json, true));
```

An alternative is to instantiate objects like in the next example 

__*__ _PHP 8_ and over:

```php
$car = new Car(brand: "BMW", color: "black", wheels: 4);
```

__*__ previous PHP versions: 

```php
$car = new Car([ "brand" => "BMW", "color" => "black", "wheels" => 4]);
```

### Methods for the objects

#### `JsonObject`

The `JsonObject` is the core class for this library. Its methods are:

- `__construct($data)` - Creates a new object from the given data
- `__get($name)` - Returns the value of the attribute with the given name
- `__set($name, $value)` - Sets the value for the attribute with the given name
- `__isset($name)` - Returns true if the attribute with the given name is set
- `__unset($name)` - Unsets the value of an optional attribute (or resets the value of a mandatory attribute).
- `toArray()` - Returns an associative array with the data of the object. The array is created recursively, visiting each of the sub-attributes for each attribute.
- `toObject()` - Returns an object with the data of the object as attributes. The array is created recursively, visiting each of the sub-attributes for each attribute.
- `toJson()` - Returns a json string with the representation of the object as standard object.
- `::fromArray($data)` - Creates an object, by parsing the given associative array into the attributes defined in the class. Each of the attributes is recursively parsed, according to the type defined to it.
- `::fromObject($data)` - Creates an object, by parsing the given object into the attributes defined in the class. Each of the attributes is recursively parsed, according to the type defined to it.

#### `JsonDict`

This object is used to deal with a dictionary coming from a json definition. The `JsonDict` class is typed to that each of the elements must be from a given type.

The `JsonDict` objects can be used as array-like objects (e.g. $jsonDict["key1"]) but (at the moment of writing this text) the type of the elements inserted in the dictionary are not checked. The type is used for parsing the content when creating the dict (e.g. using `fromArray` static function) or to dump the content to an array or an object (e.g. using `toArray` function).

The methods are:
- `toArray()`
- `toObject()`
- `::fromArray($data)`
- `::fromObject($data)`

These methods are interpreted in the same way than in the case of `JsonObject`. And the type of the elements in the dict may refer to complex types that will be considered recursively when parsing the content.

e.g. type `list[list[int]]` will be used to parse `[ [ 1, 2, 3], [ 4, 5, 6 ]]`

#### `JsonArray`

This object is very much the same than `JsonDict` with the exception that the indexes must be integer numbers. In this case `$value["key1"]` will produce an exception.

In this case, the function to append elements to the array (i.e. `[]`) is also implemented.

## Initializing values

When defining the class, it is possible to initialize the values for the objects that are newly created, and to those attributes that are optional.

There are two ways:

### Using class properties

It is possible to initialize the value of an object by using the class properties, so if the value for an attribute is set in the class, it will be copied to the instance as an attribute, if it is defined.

E.g.

```php
class User extends JsonObject {
    const ATTRIBUTES = [
        'id' => 'int',
        'name' => 'string',
        'age' => 'int',
        'emails' => 'list[string]',
        'address?' => 'Address',
        'sex?' => 'string'
    ];

    public $sex = "not revealed";
}
```

Now, the attribute `sex` is initialized to _not revealed_ instead of being _null_.

### Using the definition of the attributes

The way to make it is to define a tuple `[ <type>, <default value> ]` for the type of the object. Taking the next example:

```php
class User extends JsonObject {
    const ATTRIBUTES = [
        'id' => 'int',
        'name' => 'string',
        'age' => 'int',
        'emails' => 'list[string]',
        'address?' => 'Address',
        'sex?' => [ 'string', 'not revealed' ]
    ];
}
```

The attribute `sex` is optional when retrieving the user data. Using this new definition for the class, if `sex` is not set, the value will be set to "not revealed" instead of `null`.

An important feature is that, if the string set as _\<default value>_ corresponds to a method of the object, it will be called upon getting the value (if it has not been set, yet), and the value set for that property will be the result of the call.

E.g. 

```php
class User extends JsonObject {
    const ATTRIBUTE = [
        ...
        'birthDay?' => [ 'string', 'computeBirthDate' ]
    ]
    function computeBirthDate() {
        $now = new DateTime();
        $now->sub(DateInterval::createFromDateString("{$this->age} years"));
        return $now->format("Y-m-d");
    }
}
```

In this example, if we had not set the `birthDate` property but it is retrieved, it will be computed by subtracting the age to the current date.

## Additional tools and technical facts

### Parsing a value

If wanted to parse an arbitrary object to a `JsonObject`, it is possible to use the function `JsonObject::parse_typed_value`. This is important to be able to convert from any type to a `JsonObject`-type.

e.g.

```php
$myobject = JsonObject::parse_typed_value("list[string]", [ "my", "name", "is", "John" ]);
```

Will obtain an object of type `JsonList<string>`.

### Type checking

The default behavior of this library is to ensure that the values set for the attributes match their defined type. But that means that would mean that, as a `float` is not an `int`, setting a float to `0` will fail because `0` is an integer. In that case, the user _must_ cast the values before assigning them. To control whether to so stritcly check the type or not, it is possible to use the constant `STRICT_TYPE_CHECKING`.

> If `STRICT_TYPE_CHECKING` it is set to `True`, the types will be strictly checked and e.g. assigning `9.3` to an `int` will raise an exception. If set to `False`, the numerical types will be converted from one to each other. So e.g. if we assign `9.3` to an `int` it will be automatically truncated to `9`.

Other important type checking is when assigning an empty value (i.e. `""` or `null`) to a numeric type. In that case, we have the constant `STRICT_TYPE_CHECKING_EMPTY_ZERO`.

> If `STRICT_TYPE_CHECKING_EMPTY_ZERO` is set to `True` (the default behavior), when assigning an empty value to a numeric type, it will be considered to be `0`. i.e. assigning an empty string or a `null` value to an `int` attribute, will mean to assign `0`. If set to `False`, the library will check the types and will eventually raise an exception.

### Enhanced JsonLists

Now `JsonList` also enables to use negative indexes, so that `-1` will be the last element, `-2` the penultimate, etc.

`JsonList` object includes functions for sorting or filtering.

- `public function sort(callable $callback = null) : JsonList`: sorts the list using the given callback. If no callback is given, it will sort the list using the default comparison function.
- `public function filter(callable $callback) : JsonList`: filters the list using the given callback. The callback must return a boolean value. If the callback returns `true`, the element will be included in the resulting list. If it returns `false`, the element will be discarded.