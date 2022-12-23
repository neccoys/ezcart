<?php

namespace Neccoys\EzCart\Contracts;

/**
 * Interface CouponContract
 */
interface CouponContract
{
    /**
     * CouponContract constructor.
     * @param $code
     * @param $value
     */
    public function __construct($code, $value);

    /**
     * @param $price
     * @return mixed
     */
    public function discount($price);

}
