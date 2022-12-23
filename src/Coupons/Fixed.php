<?php


namespace Neccoys\EzCart\Coupons;


use Neccoys\EzCart\Helpers\EzCartHelper;
use Neccoys\EzCart\Contracts\CouponContract;
use Neccoys\EzCart\Traits\CouponTrait;

/**
 * Class Fixed
 */
class Fixed implements CouponContract
{
    use CouponTrait;

    /**
     * Fixed constructor.
     * @param $code
     * @param $value
     * @param array $options
     */
    public function __construct($code, $value, $options = [])
    {
        $this->code = $code;
        $this->value = $value;
        $this->setOptions($options);
    }

    /**
     * @param $price
     * @return float|int|mixed
     */
    public function discount($price)
    {
        $total = EzCartHelper::Sub($price, $this->value) ?? 0;
        $this->discounted = empty($total) ? 0 : $this->value;

        return $total;
    }

}
