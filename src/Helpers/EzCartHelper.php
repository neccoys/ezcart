<?php


namespace Neccoys\EzCart\Helpers;


class EzCartHelper
{
    public static function Mul($a, $b)
    {
        return floatval(bcmul($a, $b, config('ezcart.helper_scale', 4)));
    }

    public static function Add(...$nums)
    {
        $total = 0;
        foreach ($nums as $num) {
            $total = bcadd($total, $num, config('ezcart.helper_scale', 4));
        }

        return floatval($total);
    }

    public static function Sub($a, $b)
    {
        return floatval(bcsub($a, $b, config('ezcart.helper_scale', 4)));
    }

    public static function Precision($v, $precision)
    {
        $pow = pow(10, $precision);
        return bcdiv(ceil(bcmul($v, $pow,  $precision)), $pow, $precision);
    }
}
