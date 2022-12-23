<?php

namespace Neccoys\EzCart\Coupons;


use Neccoys\EzCart\Helpers\EzCartHelper;
use Neccoys\EzCart\Contracts\CouponContract;
use Neccoys\EzCart\Exceptions\CouponException;
use Neccoys\EzCart\Traits\CouponTrait;


/**
 * Class Percentage
 */
class Percentage implements CouponContract
{
    use CouponTrait;

    /**
     * Percentage constructor.
     * @param $code
     * @param $value
     * @param array $options
     * @throws CouponException
     */
    public function __construct($code, $value, $options = [])
    {
        $this->code = $code;
        if ($value > 1) {
            $this->message = 'Invalid value for a percentage coupon. The value must be between 0 and 1.';

            throw new CouponException($this->message);
        }
        $this->value = $value;

        $this->setOptions($options);
    }

    /**
     * @param $price
     * @return float|int|mixed
     */
    public function discount($price)
    {
        $dif = EzCartHelper::Mul($price, EzCartHelper::Sub(1, $this->value));

        if ($this->maxDiscount > 0 && $dif > $this->maxDiscount) {
            $dif = $this->maxDiscount;
        }

        $total = EzCartHelper::Sub($price, $dif) ?? 0;
        $this->discounted = $dif;

        return $total;
    }
}
