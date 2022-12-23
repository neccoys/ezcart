<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The caching prefix used to lookup the cart
    |--------------------------------------------------------------------------
    |
    */
    'cache_prefix' => 'ezcart',

    /*
    |--------------------------------------------------------------------------
    | database settings
    |--------------------------------------------------------------------------
    |
    | Here you can set the name of the table you want to use for
    | storing and restoring the cart session id.
    |
    */
    'database' => [
        'table' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | If true, lets you supply and retrieve all prices in cents.
    | To retrieve the prices as integer in cents, set the $format parameter
    | to false for the various price functions. Otherwise you will retrieve
    | the formatted price instead.
    | Make sure when adding products to the cart, adding coupons, etc, to
    | supply the price in cents too.
    |--------------------------------------------------------------------------
    |
    */
    'prices_in_cents' => true,
    'math_type' => 'ceil',

    'helper_scale' => 4,

    /*
    |--------------------------------------------------------------------------
    | Sets the tax for the cart and items, you can change per item
    | via the object later if needed
    |--------------------------------------------------------------------------
    |
    */

    'tax' => 0,

    /*
    |--------------------------------------------------------------------------
    | Allows you to choose if the discounts applied to fees
    |--------------------------------------------------------------------------
    |
    */
    'fees_taxable' => false,

    /*
    |--------------------------------------------------------------------------
    | Allows you to configure if a user can apply multiple coupons
    |--------------------------------------------------------------------------
    |
    */
    'multiple_coupons' => false,

    /*
    |--------------------------------------------------------------------------
    | The default item model for your relations
    |--------------------------------------------------------------------------
    |
    */
    'item_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Binds your data into the correct spots for EzCart
    |--------------------------------------------------------------------------
    |
    */
    'item_model_bindings' => [
        \Neccoys\EzCart\Carts\CartItem::ITEM_ID      => 'id',
        \Neccoys\EzCart\Carts\CartItem::ITEM_NAME    => 'name',
        \Neccoys\EzCart\Carts\CartItem::ITEM_PRICE   => 'price',
        \Neccoys\EzCart\Carts\CartItem::ITEM_OPTIONS => [
            // put columns here for additional options,
            // these will be merged with options that are passed in
            // e.x
            // tax => .07
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The default item relations to the item_model
    |--------------------------------------------------------------------------
    |
    */
    'item_model_relations' => [],

    /*
    |--------------------------------------------------------------------------
    | This allows you to use multiple devices based on your logged in user
    |--------------------------------------------------------------------------
    |
    */
    'cross_devices' => false,

    /*
    |--------------------------------------------------------------------------
    | This allows you to use custom guard to get logged in user
    |--------------------------------------------------------------------------
    |
    */
    'guard' => null,

    /*
    |--------------------------------------------------------------------------
    | This allows you to exclude any option from generating CartItem hash
    |--------------------------------------------------------------------------
    |
    */
    'exclude_from_hash' => [],
];
