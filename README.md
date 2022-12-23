## EzCart 

## Introduction
A easy cart for Laravel

## Installation
```shell script
// composer
composer require neccoys/ezcart

// publish
php artisan vendor:publish --provider="Neccoys\EzCart\EzCartServiceProvider"
```

## Quick Usage Example

### 
```php

// Add item
EzCart::add(
    $itemID,
    $name = null,
    $qty = 1,
    $price = '0.00',
    $options = [],
    $lineItem = false
)

$item = EzCart::add(2, 'T-shirt', 200, 15, [
    'size' => 'L'
]);

$itemHash = $item->getHash();

// Update item
EzCart::updateItem($item->getHash(), 'size', 'M');

// Remove item
EzCart::removeItem($item->getHash());

// Find items (array)
$items = EzCart::find(['name' => 'product001']);

// Empty cart
EzCart::emptyCart();

// Destroy cart
EzCart::destroyCart();

```

## Coupons & Fee
```php
// Fixed amount
// you can set 'minAmount' 
$couponFixed = new Fixed("coupon002", 35,  [
    'description' => "test fixed coupon",
    'minAmount' => 45,
]);

EzCart::addCoupons($couponFixed);


// Percentage
// you can set 'minAmount', 'maxDiscount' 
$couponPercentage = new Percentage("coupon001", .8,  [
    'description' => "test percent",
    'minAmount' => 0,
    'maxDiscount' => 0
]);

EzCart::addCoupons($couponPercentage);

```

## Total
```php
// Items totals
EzCart::itemTotals();

// Totals (tax)
EzCart::total();

// SubTotal (coupons + tax)
EzCart::subTotal();

```
