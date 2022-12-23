<?php

namespace Neccoys\EzCart\Traits;

use Illuminate\Support\Arr;
use Neccoys\EzCart\Carts\CartItem;
use Neccoys\EzCart\Exceptions\InvalidPrice;
use Neccoys\EzCart\Exceptions\InvalidQuantity;

/**
 * Trait CartOptionsMagicMethodsTrait
 */
trait CartOptionsMagicMethodsTrait
{
    public $options = [];

    /**
     * @param $option
     * @return array|\ArrayAccess|mixed
     */
    public function __get($option)
    {
        return Arr::get($this->options, $option);
    }

    /**
     * @param $option
     * @param $value
     * @throws InvalidPrice
     * @throws InvalidQuantity
     */
    public function __set($option, $value)
    {
        switch ($option) {
            case CartItem::ITEM_QTY:
                if (!is_numeric($value) || $value <= 0) {
                    throw new InvalidQuantity('The quantity must be a valid number');
                }
                break;
            case CartItem::ITEM_PRICE:
                if (!is_numeric($value)) {
                    throw new InvalidPrice('The price must be a valid number');
                }
                break;
        }

        $changed = (!empty(Arr::get($this->options, $option)) && Arr::get($this->options, $option) != $value);
        Arr::set($this->options, $option, $value);

        if ($changed) {
            if (is_callable([$this, 'generateHash'])) {
                $this->generateHash();
            }
        }
    }

    /**
     * @param $option
     * @return bool
     */
    public function __isset($option)
    {
        if (isset($this->options[$option])) {
            return true;
        } else {
            return false;
        }
    }
}
