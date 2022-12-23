<?php


namespace Neccoys\EzCart\Carts;


use Neccoys\EzCart\Helpers\EzCartHelper;
use Neccoys\EzCart\Traits\CartOptionsMagicMethodsTrait;

/**
 * Class CartFee
 */
class CartFee
{
    use CartOptionsMagicMethodsTrait;

    public $taxRate;
    public $amount;
    public $withTax;

    /**
     * CartFee constructor.
     * @param $amount
     * @param bool $withTax
     * @param array $options
     */
    public function __construct($amount, $withTax = true, $options = [])
    {
        $this->amount = floatval($amount);
        $this->withTax = $withTax;
        $this->taxRate = isset($options['tax']) ? $options['tax'] == 0 ? config('ezcart.tax', 0) : $options['tax'] : config('ezcart.tax', 0);
        $this->options = $options;
    }

    public function getAmount()
    {
        return EzCartHelper::Add($this->amount, $this->getTax());
    }

    public function getTax()
    {
        return (config('ezcart.tax', 0) > 0 && config('ezcart.fees_taxable') && $this->withTax) ? EzCartHelper::Mul($this->amount,  $this->taxRate) : 0 ;
    }

}
