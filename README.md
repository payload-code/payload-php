# Payload PHP Library

A PHP library for integrating [Payload](https://payload.com).

## Installation

## Install using composer

```bash
composer require payload/payload-api
```

## Install and run manually

### Dependencies

```
sudo apt install php-curl
```

### Require `import.php`

```php
<?php
require_once('payload-php/import.php');
?>
```

## Get Started

Once you've installed the Payload PHP library to your environment,
we recommend
using the shorthand alias name of `pl` for `Payload\API`.

```php
<?php
require_once('vendor/autoload.php');
use Payload\API as pl;
?>
```

### API Authentication

To authenticate with the Payload API, you'll need a live or test API key. API
keys are accessible from within the Payload dashboard.

```php
use Payload\API as pl;
pl::$api_key = 'secret_key_3bW9JMZtPVDOfFNzwRdfE';
```


### Testing the PHP Library

Tests are contained within the Tests/ directory. To run a test file enter the
command in terminal

```  ./vendor/bin/phpunit Tests/{__FILENAME__}.php ```

Test execution options can be viewed using the ``` ./vendor/bin/phpunit ``` command.


### Creating an Object

Interfacing with the Payload API is done primarily through Payload Objects. Below is an example of
creating a customer using the `Payload\Customer` object.


```php
<?php
# Create a Customer
$customer = Payload\Customer::create(array(
    'email'=>'matt.perez@example.com',
    'name'=>'Matt Perez'
));
?>
```


```php
<?php
# Create a Payment
$payment = Payload\Transaction::create(array(
    'amount'=>100.0,
    'type'=>'payment',
    'payment_method'=>new Payload\PaymentMethod(array(
        'card'=>array('card_number'=>'4242 4242 4242 4242'),
        'type'=>'card'
    ))
));
?>
```

### Accessing Object Attributes

Object attributes are accessible through dot notation.

```php
<?php
$customer->name;
?>
```

### Updating an Object

Updating an object is a simple call to the `update` object method.

```php
<?php
# Updating a customer's email
$customer->update(array( 'email'=>'matt.perez@newwork.com' ))
?>
```

### Selecting Objects

Objects can be selected using any of their attributes.

```php
<?php
# Select a customer by email
$customers = Payload\Customer::filter_by(
    pl::attr()->email->eq('matt.perez@example.com')
);
?>
```

Use the `pl::attr()` attribute helper
interface to write powerful queries with a little extra syntax sugar.

```php
$payments = Payload\Transaction::filter_by(
    pl::attr()->amount->gt(100),
    pl::attr()->amount->lt(200),
    pl::attr()->description->contains("Test"),
    pl::attr()->created_at->gt('2019-02-01')
)->all()
```

## Documentation

To get further information on Payload's PHP library and API capabilities,
visit the unabridged [Payload Documentation](https://docs.payload.com/?php).
