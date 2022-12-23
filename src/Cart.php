<?php


namespace Neccoys\EzCart;

/**
 * Class Cart
 */
class Cart
{
    public $taxRate;
    public $fees = [];
    public $items;
    public $instance;
    public $coupons = [];
    public $multipleCoupons;

    /**
     * Cart constructor.
     * @param string $instance
     */
    public function __construct($instance = 'default')
    {
        $this->instance = $instance;
        $this->taxRate = floatval(config('ezcart.tax', 0));
        $this->multipleCoupons = config('ezcart.multiple_coupons');
    }
}
