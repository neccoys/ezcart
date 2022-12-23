<?php


namespace Neccoys\EzCart\Carts;


use Neccoys\EzCart\Helpers\EzCartHelper;
use Neccoys\EzCart\Exceptions\ModelNotFound;
use Neccoys\EzCart\EzCart;
use Neccoys\EzCart\Traits\CartOptionsMagicMethodsTrait;

/**
 * Class CartItem.
 *
 * @property int    id
 * @property int    qty
 * @property float  price
 * @property string name
 * @property array  options
 */
class CartItem
{
    const ITEM_ID = 'id';
    const ITEM_QTY = 'qty';
    const ITEM_PRICE = 'price';
    const ITEM_NAME = 'name';
    const ITEM_OPTIONS = 'options';

    use CartOptionsMagicMethodsTrait;

    protected $itemHash;
    protected $excludeFromHash;
    protected $itemModel;
    protected $itemModelRelations;

    public $lineItem;
    public $useCoupon = true;
    public $active = true;
    public $coupons = [];

    public $discounted = 0;

    /**
     * CartItem constructor.
     * @param $id
     * @param $name
     * @param $qty
     * @param $price
     * @param array $options
     * @param false $lineItem
     */
    public function __construct($id, $name, $qty, $price, $options = [], $lineItem = false)
    {
        $this->id = $id;
        $this->qty = $qty;
        $this->name = $name;
        $this->price = $price;
        $this->lineItem = $lineItem;
        $this->itemModel = config('ezcart.item_model', null);
        $this->itemModelRelations = config('ezcart.item_model_relations', []);
        $this->excludeFromHash = config('ezcart.exclude_from_hash', []);

        foreach ($options as $option => $value) {
            $this->$option = $value;
        }
    }

    public function generateHash($force = false)
    {
        if ($this->lineItem === false) {
            $this->itemHash = null;

            $cartItemArray = (array) clone $this;

            unset($cartItemArray['discounted']);
            unset($cartItemArray['options']['qty']);

            foreach ($this->excludeFromHash as $option) {
                unset($cartItemArray['options'][$option]);
            }

            ksort($cartItemArray['options']);

            $this->itemHash = EzCart::Hash($cartItemArray);
        } elseif ($force || empty($this->itemHash) === true) {
            $this->itemHash = EzCart::RandomHash();
        }

        app('events')->dispatch(
            'ezcart.updateItem',
            [
                'item'    => $this,
                'newHash' => $this->itemHash,
            ]
        );

        return $this->itemHash;
    }

    public function getHash()
    {
        return $this->itemHash;
    }

    public function setModel($itemModel, $relations = [])
    {
        if (!class_exists($itemModel)) {
            throw new ModelNotFound('Could not find relation model');
        }

        $this->itemModel = $itemModel;
        $this->itemModelRelations = $relations;
    }

    public function getModel()
    {
        $itemModel = (new $this->itemModel())->with($this->itemModelRelations)->find($this->id);

        if (empty($itemModel)) {
            throw new ModelNotFound('Could not find the item model for '.$this->id);
        }

        return $itemModel;
    }

    public function disable()
    {
        $this->active = false;
        $this->update();
    }

    public function enable()
    {
        $this->active = true;
        $this->update();
    }

    public function find($data)
    {
        foreach ($data as $key => $value) {
            if ($this->$key !== $value) {
                return false;
            }
        }

        return $this;
    }

    public function update()
    {
        $this->generateHash();
        app('ezcart')->update();
    }

    public function coupon($useCoupon = true)
    {
        $this->useCoupon = $useCoupon;
        $this->update();
    }

    public function total()
    {
        return $this->active ? EzCartHelper::Mul($this->price, $this->qty) : 0;
    }

}
