<?php

namespace Neccoys\EzCart\Traits;

use Carbon\Carbon;
use Neccoys\EzCart\Carts\CartItem;
use Neccoys\EzCart\Exceptions\CouponException;
use Neccoys\EzCart\EzCart;

/**
 * Trait CouponTrait
 */
trait CouponTrait
{
    public $code;
    public $value;
    public $discounted = 0;
    public $appliedToCart = true;

    use CartOptionsMagicMethodsTrait;

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
    }

    public function checkMinAmount($total, $field = 'minAmount')
    {
        return $total >= $this->$field;
    }

}
