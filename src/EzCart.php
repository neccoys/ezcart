<?php


namespace Neccoys\EzCart;


use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Neccoys\EzCart\Carts\CartFee;
use Neccoys\EzCart\Carts\CartItem;
use Neccoys\EzCart\Contracts\CouponContract;
use Neccoys\EzCart\Coupons\Fixed;
use Neccoys\EzCart\Helpers\EzCartHelper;

class EzCart
{
    const SERVICE = 'ezcart';

    protected $events;
    protected $session;
    protected $authManager;

    public $cart;
    public $prefix;
    public $itemModel;
    public $itemModelRelations;

    public function __construct(SessionManager $session, Dispatcher $events, AuthManager $authManager)
    {
        $this->session = $session;
        $this->events = $events;
        $this->authManager = $authManager->guard(config('ezcart.guard', null));
        $this->prefix = config('ezcart.cache_prefix', 'ezcart');
        $this->itemModel = config('ezcart.item_model', null);
        $this->itemModelRelations = config('ezcart.item_model_relations', []);

        $this->setInstance($this->session->get($this->prefix.'.instance', 'default'));
    }

    public function setInstance($instance = 'default')
    {
        $this->get($instance);

        $this->session->put($this->prefix.'.instance', $instance);

        if (!in_array($instance, $this->getInstance())) {
            $this->session->push($this->prefix.'.instances', $instance);
        }
        $this->events->dispatch('ezcart.new');

        return $this;
    }

    public function get($instance = 'default')
    {
        if (config('ezcart.cross_devices', false) && $this->authManager->check()) {
            if (!empty($cartSessionID = $this->authManager->user()->cart_session_id)) {
                $this->session->setId($cartSessionID);
                $this->session->start();
            }
        }

        if (empty($this->cart = $this->session->get($this->prefix.'.'.$instance))) {
            $this->cart = new Cart($instance);
        }

        return $this;
    }

    public function getInstance()
    {
        return $this->session->get($this->prefix.'.instances', []);
    }

    public function add(
        $itemID,
        $name = null,
        $qty = 1,
        $price = '0.00',
        $options = [],
        $lineItem = false
    ) {
        if (!empty(config('ezcart.item_model'))) {
            $itemModel = $itemID;

            if (!$this->isItemModel($itemModel)) {
                $itemModel = (new $this->itemModel())->with($this->itemModelRelations)->find($itemID);
            }

            if (empty($itemModel)) {
                throw new \Exception('Could not find the item '.$itemID);
            }

            $bindings = config('ezcart.item_model_bindings');

            $itemID = $itemModel->{$bindings[CartItem::ITEM_ID]};

            if (is_int($name)) {
                $qty = $name;
            }

            $name = $itemModel->{$bindings[CartItem::ITEM_NAME]};
            $price = $itemModel->{$bindings[CartItem::ITEM_PRICE]};

            $options['model'] = $itemModel;
            $options = array_merge($options, $this->getItemModelOptions($itemModel, $bindings[CartItem::ITEM_OPTIONS]));

        }

        $item = $this->addItem(new CartItem(
            $itemID,
            $name,
            $qty,
            $price,
            $options,
            $lineItem
        ));

        $this->update();

        return $this->getItem($item->getHash());
    }

    public function addItem(CartItem $cartItem)
    {
        $itemHash = $cartItem->generateHash();

        if ($this->getItem($itemHash)) {
            $this->getItem($itemHash)->qty += $cartItem->qty;
        } else {
            $this->cart->items[] = $cartItem;
        }

        app('events')->dispatch(
            'ezcart.addItem',
            $cartItem
        );

        return $cartItem;
    }

    public function getItem($itemHash)
    {
        return Arr::get($this->getItems(), $itemHash);
    }

    public function getItems()
    {
        $items = [];
        if (isset($this->cart->items) === true) {
            foreach ($this->cart->items as $item) {
                $items[$item->getHash()] = $item;
            }
        }

        return $items;
    }

    public function find($data)
    {
        $matches = [];

        foreach ($this->getItems() as $item) {
            if ($item->find($data)) {
                $matches[] = $item;
            }
        }

        return $matches;
    }

    public function updateItem($itemHash, $key, $value)
    {
        if (empty($item = $this->getItem($itemHash)) === false) {
            if ($key == 'qty' && $value == 0) {
                return $this->removeItem($itemHash);
            }

            $item->$key = $value;
        }

        $this->update();

        return $item;
    }

    public function removeItem($itemHash)
    {
        if (empty($this->cart->items) === false) {
            foreach ($this->cart->items as $itemKey => $item) {
                if ($item->getHash() == $itemHash) {
                    unset($this->cart->items[$itemKey]);
                    break;
                }
            }

            $this->events->dispatch('ezcart.removeItem', $item);

            $this->update();
        }
    }

    public function count($withQty = true)
    {
        $count = 0;

        foreach ($this->getItems() as $item) {
            if ($withQty) {
                $count += $item->qty;
            } else {
                $count++;
            }
        }

        return $count;
    }

    public function itemTotals()
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            if ($item->active) {
                $total = EzCartHelper::Add($total, $item->total());
            }
        }

        return $total;
    }

    public function emptyCart()
    {
        unset($this->cart->items);

        $this->update();

        $this->events->dispatch('ezcart.empty', $this->cart->instance);
    }

    /**
     * Completely destroys cart and anything associated with it.
     */
    public function destroyCart()
    {
        $instance = $this->cart->instance;

        $this->session->forget($this->prefix.'.'.$instance);

        $this->events->dispatch('ezcart.destroy', $instance);

        $this->cart = new Cart($instance);

        $this->update();
    }

    public function update()
    {
        $this->session->put($this->prefix.'.'.$this->cart->instance, $this->cart);

        if (config('ezcart.cross_devices', false) && $this->authManager->check()) {
            $this->authManager->user()->cart_session_id = $this->session->getId();
            $this->authManager->user()->save();
        }

        $this->session->reflash();
        $this->session->save();

        $this->events->dispatch('ezcart.update', $this->cart);
    }

    public function addCoupons(CouponContract ...$coupons)
    {
        if (!$this->cart->multipleCoupons) {
            $this->cart->coupons = [];
            $this->cart->coupons[$coupons[0]->code] = $coupons[0];
        }else{
            foreach ($coupons as $coupon) {
                $this->cart->coupons[$coupon->code] = $coupon;
            }
        }

        $this->update();
    }

    public function getCoupon($code)
    {
        return Arr::get($this->cart->coupons, $code);
    }

    public function getCoupons()
    {
        return $this->cart->coupons;
    }

    public function resetCouponsDiscount()
    {
        foreach ($this->getCoupons() as $coupon) {
             $coupon->discounted = 0;
        }
        $this->update();
    }

    public function removeCoupon($code)
    {
        $this->removeCouponFromItems($code);
        Arr::forget($this->cart->coupons, $code);
        $this->update();
    }

    public function removeCoupons()
    {
        $this->removeCouponFromItems();
        $this->cart->coupons = [];
        $this->update();
    }

    private function removeCouponFromItems($code = null)
    {
        foreach ($this->getItems() as $item) {
            if (empty($code)) {
                $item->coupons = [];
            }else if (isset($item->coupons) && in_array($code, $item->coupons)){
                unset($item->coupons[array_search($code, $item->coupons)]);
            }
        }
    }

    public function getCouponsDiscount()
    {
        $total = 0;
        foreach ($this->getCoupons() as $coupon) {
            $total = EzCartHelper::Add($total, $coupon->discounted);
        }

        return $total;
    }

    public function itemWithCoupon()
    {
        return $this->itemCoupon();
    }

    public function itemWithoutCoupon()
    {
        return $this->itemCoupon(false);
    }

    private function itemCoupon($useCoupon = true)
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            if ($item->useCoupon === $useCoupon) {
                $total = EzCartHelper::Add($total, $item->total());
            }
        }
        return $total;
    }

    public function itemsCouponTotals($checkCoupon = true)
    {
        $total = 0;
        if ($checkCoupon) {
            $withCouponTotal = $this->calculateCouponTotal();
            $withoutCouponTotal = $this->itemWithoutCoupon();
            $total = EzCartHelper::Add($withCouponTotal, $withoutCouponTotal);
        }else{
            $total = $this->calculateAllCouponTotal();
        }

        return $total;
    }

    public function calculateAllCouponTotal()
    {
        $total = $this->itemTotals();

        // use fixed coupons first
        $percentageCoupons = [];
        foreach ($this->getCoupons() as $coupon) {
            if ($coupon instanceof Fixed) {
                if ($coupon->checkMinAmount($total)) {
                    $total = $coupon->discount($total);
                }
            }else{
                $percentageCoupons[] = $coupon;
            }
        }

        // use percentage coupons
        foreach ($percentageCoupons as $coupon) {
            if ($coupon->checkMinAmount($total)) {
                $total = $coupon->discount($total);
            }
        }

        return $total;
    }

    public function calculateCouponTotal()
    {
        $total = $this->itemWithCoupon();

        // use fixed coupons first
        $percentageCoupons = [];
        foreach ($this->getCoupons() as $coupon) {
            if ($coupon instanceof Fixed) {
                if ($coupon->checkMinAmount($total)) {
                    $total = $coupon->discount($total);
                }
            }else{
                $percentageCoupons[] = $coupon;
            }
        }

        // use percentage coupons
        foreach ($percentageCoupons as $coupon) {
            if ($coupon->checkMinAmount($total)) {
                $total = $coupon->discount($total);
            }
        }

        return $total;
    }

    public function total()
    {

        $total = $this->itemTotals();

        $taxAmount = $this->getItemsTaxAmount($total);

        $total = EzCartHelper::Add($total, $taxAmount);

        return config('ezcart.prices_in_cents', true) ? $total : intval(call_user_func(config('ezcart.math_type', 'ceil'), $total));
    }

    public function subTotal($checkCoupon = false)
    {
        $itemTotal = $this->itemsCouponTotals($checkCoupon);

        $fee = $this->getFeesTotal();

        $taxAmount = $this->getItemsTaxAmount($itemTotal);

        $subTotal = EzCartHelper::Add($itemTotal, $fee, $taxAmount);


        return config('ezcart.prices_in_cents', true) ? $subTotal : intval(call_user_func(config('ezcart.math_type', 'ceil'), $subTotal));
    }

    public function getItemsTaxAmount($total = null)
    {
        if (empty($total)) {
            $total = $this->itemsCouponTotals();
        }

        if ($this->cart->taxRate > 0) {
            return EzCartHelper::Mul($total, $this->cart->taxRate);
        }

        return 0;
    }

    public function addFee($name, $amount, $withTax = true, array $options = [])
    {
        Arr::set($this->cart->fees, $name, new CartFee($amount, $withTax, $options));

        $this->update();
    }

    public function getFee($name)
    {
        return Arr::get($this->cart->fees, $name, new CartFee(0));
    }

    public function getFees()
    {
        return $this->cart->fees;
    }

    public function getFeesTotal()
    {
        $total = 0;

        foreach ($this->getFees() as $fee) {
            $total = EzCartHelper::Add($total, $fee->getAmount());
        }

        return $total;
    }

    public function removeFee($name)
    {
        Arr::forget($this->cart->fees, $name);

        $this->update();
    }

    public function removeFees()
    {
        $this->cart->fees = [];

        $this->update();
    }

    private function getItemModelOptions(Model $itemModel, $options = [])
    {
        $itemOptions = [];
        foreach ($options as $option) {
            $itemOptions[$option] = $this->getFromModel($itemModel, $option);
        }

        return array_filter($itemOptions, function ($value) {
            if ($value !== false && empty($value)) {
                return false;
            }

            return true;
        });
    }

    private function getFromModel(Model $itemModel, $attr, $defaultValue = null)
    {
        $variable = $itemModel;

        if (!empty($attr)) {
            foreach (explode('.', $attr) as $attr) {
                $variable = Arr::get($variable, $attr, $defaultValue);
            }
        }

        return $variable;
    }

    private function isItemModel($itemModel)
    {
        if (is_object($itemModel) && get_class($itemModel) == config('ezcart.item_model')) {
            return true;
        }

        return false;
    }

    public static function Hash($data)
    {
        return md5(json_encode($data));
    }

    public static function RandomHash() {
        return Str::random(40);
    }
}
