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
        'name' => 'str',
        'age' => 'int',
        'emails' => 'list[str]',
        'address?' => 'Address',
    ];
}

class Address extends JsonObject {
    const ATTRIBUTES = [
        'street' => 'str',
        'number' => 'int',
        'city' => 'str',
        'country' => 'str',
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
        'name' => 'str',
        'age' => 'int',
        'emails' => 'list[str]',
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
- str: string
- bool: boolean
- list[type]: list of objects of type _type_.
- dict[type]: dictionary of objects of type _type_. The keys for each entry of the dictionary is converted to strings.
- object: is a class name which must be a subclass of `JsonObject`.

### Optional values

When defining the name of the attributes, one can add a `?` at the end of the name to indicate that the attribute is optional. For example, the attribute name `address?` in the use-case section is optional.
 
Each field is considered to be mandatory so that it must exist in the parsed object (or array). Moreover, the object must be of the type defined (i.e. it must be correctly parsed by the specific type). 

If one of the values is null, but the field is not defined as optional (i.e. it does not contain the trailing `?` in the name), then an exception is thrown.
 
### Inheritance

`JsonObject`s are also able to inherit attributes from their parent classes. Take the following example:

```php
class Vehicle extends JsonObject {
    const ATTRIBUTES = [
        "brand" => "str",
        "color" => "str"
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

## Default values

When defining the class, it is possible to set the default values for the objects that are newly created, and to those attributes that are optional.

The way to make it is to define a tuple `[ <type>, <default value> ]` for the type of the object. Taking the next example:

```php
class User extends JsonObject {
    const ATTRIBUTES = [
        'id' => 'int',
        'name' => 'str',
        'age' => 'int',
        'emails' => 'list[str]',
        'address?' => 'Address',
        'sex?' => [ 'str', 'not revealed' ]
    ];
}
```

The attribute `sex` is optional when retrieving the user data. Using this new definition for the class, if `sex` is not set, the value will be set to "not revealed" instead of `null`.