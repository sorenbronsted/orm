# ORM

This is a simple ORM database mapper which delivers some convenience methods to query, create and update objects.
It does not support associations. Instead I use the convention that a foreign key is the name of the table appended
'_uid'. The only other convention is that an object must have a property named 'uid' and when you call save
and the uid == 0 it will be converted to an insert statement otherwise it will be converted to an update statement.

It is a base on my older similar [package](https://packagist.org/packages/sbronsted/libdatabase) but with now
you can use typed properties.

## Installation
This package can be installed with [Composer](https://getcomposer.org/)

    `composer install bronsted\fiberloop`

## Example

Below is defined a Sample class

```php
class Sample extends DbObject
{
    protected int $uid = 0;
    protected ?string $name = null;
    protected ?DateTime $created = null;
}
```
This class will have a corresponding table name sample where properties are columns.

As you se the properties are describe by types, which means that when the object is loaded from a table row,
the columns are converted to the property type. In that way the date is not a string, but an date object with
meaningful operation.

To create an object and persist it
```php
$sample = new Sample();
$uid = $sample->save();
```

To to find one or more objects
```php
// returns a single object
$sample = Sample::getByUid($uid);

// returns 0 or more objects
$samples = Sample::getBy(['name' => 'something%']);
```

To change a property and persist it
```php
$sample = Sample::getByUid($uid);
$sample->name = 'Hello';
$sample->save();
```

To delete an object
```php
$sample = Sample::getByUid($uid);
$sample->delete();
```

You also have access to the lower level database function in the DB class, which provides
transformations for DbObject to sql or you can write the sql your self.

## Configuration

The connection is done with plain php PDO.
```php
$pdo = new PDO('sqlite::memory:');
$dbCon = new DbConnection($pdo);
Db::setConnection($dbCon);
```
