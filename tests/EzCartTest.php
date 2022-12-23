<?php


use Neccoys\EzCart\Coupons\Fixed;
use Neccoys\EzCart\Coupons\Percentage;
use Neccoys\EzCart\Tests\EzCartTestTrait;
use Orchestra\Testbench\TestCase;

class EzCartTest extends TestCase
{

    use EzCartTestTrait;

    public function testAdd() {

        $item = $this->ezcart->add(2, 'T-shirt', 200, 15, [
            'size' => 'L'
        ]);

        $this->assertEquals($item->id, 2);
        $this->assertEquals($item->id, 2);
        $this->assertEquals($item->qty, 200);
        $this->assertEquals($item->name, 'T-shirt');
        $this->assertEquals($item->price, 15);
        $this->assertEquals($item->size, 'L');
    }

    public function testGetItem()
    {
        $item = $this->addItem1(10, 30);

        $product = $this->ezcart->getItem($item->getHash());

        $this->assertEquals($product->name, $item->name);
        $this->assertEquals($product->qty, 10);
        $this->assertEquals($product->price, 30);
    }

    public function testGetItems()
    {
        $item1 = $this->addItem1(3);
        $item2 = $this->addItem2(5);

        $this->assertCount(2, $this->ezcart->getItems());

        $qty = 0;
        $itemList = [];
        foreach ($this->ezcart->getItems() as $item) {
            $qty += $item->qty;
            $itemList[] = $item->getHash();
        }

        $this->assertEquals(8, $qty);
        $this->assertEquals(true, in_array($item1->getHash(), $itemList));
        $this->assertEquals(true, in_array($item2->getHash(), $itemList));
    }

    public function testCount()
    {
        $this->addItem1(4);

        $this->assertEquals(4, $this->ezcart->count());
        $this->assertEquals(1, $this->ezcart->count(false));
    }

    public function testFind()
    {
        $item1 = $this->addItem1(3, 100, [
            'size' => 'L'
        ]);
        $item2 = $this->addItem2(5, 120, [
            'size' => 'M'
        ]);

        $items = $this->ezcart->find([
            'size' => 'M'
        ]);

        $this->assertEquals(1, count($items));
        $this->assertEquals($items[0], $item2);

        $items2 = $this->ezcart->find([
            'name' => 'product001'
        ]);

        $this->assertEquals(1, count($items));
        $this->assertEquals($items2[0], $item1);

    }

    public function testUpdateItem()
    {
        $item = $this->addItem1();

        $this->ezcart->updateItem($item->getHash(), 'qty', 5);
        $this->ezcart->updateItem($item->getHash(), 'size', 'M');

        $product = $this->ezcart->getItem($item->getHash());

        $this->assertEquals(5, $this->ezcart->count());
        $this->assertEquals('M', $product->size);
    }

    public function testRemoveItem()
    {
        $item1 = $this->addItem1(3);
        $item2 = $this->addItem2(7);

        $this->assertEquals(10, $this->ezcart->count());
        $this->assertEquals(2, $this->ezcart->count(false));

        $this->ezcart->removeItem($item2->getHash());

        $this->assertEquals(3, $this->ezcart->count());
        $this->assertCount(1, $this->ezcart->getItems());

        $this->ezcart->removeItem($item1->getHash());

        $this->assertCount(0, $this->ezcart->getItems());
    }

    public function testEmptyCart()
    {
        $this->addItem1(2);
        $this->addItem2(3);

        $this->ezcart->emptyCart();

        $this->assertEquals(0, $this->ezcart->count());
    }

    public function testItemTotals()
    {
        $this->addItem1(2, 5);
        $this->addItem2(3, 7);

        $this->assertEquals(31, $this->ezcart->itemTotals());
    }

    public function testDestroyCart()
    {
        $this->addItem1(3, 100);
        $this->ezcart->cart->multipleCoupons = true;

        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 0,
        ]);

        $this->ezcart->addCoupons($couponPercentage);

        $this->ezcart->destroyCart();

        $this->assertEquals([], $this->ezcart->getItems());
        $this->assertEquals([], $this->ezcart->cart->coupons);
    }

    public function testTotal()
    {
        $this->addItem1(3, 100);
        $item2 = $this->addItem2(2, 160);

        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 0,
        ]);

        $couponFixed = new Fixed("coupon002", 35,  [
            'description' => "test fixed coupon",
            'minAmount' => 45,
        ]);

        $this->ezcart->cart->multipleCoupons = true;

        $this->ezcart->addCoupons($couponPercentage, $couponFixed);


        $total = $this->ezcart->total();

        $this->assertEquals(651, $total);

        $this->ezcart->resetCouponsDiscount();
        $item2->disable();
        $total2 = $this->ezcart->total();

        $this->assertEquals(315, $total2);
    }

    public function testSubTotal()
    {
        $this->addItem1(3, 100);
        $this->addItem2(2, 160);

        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 0,
        ]);

        $couponFixed = new Fixed("coupon002", 35,  [
            'description' => "test fixed coupon",
            'minAmount' => 45,
        ]);

        $this->ezcart->cart->multipleCoupons = true;

        $this->ezcart->addCoupons($couponPercentage, $couponFixed);

        $total = $this->ezcart->subTotal();


        $this->assertEquals( floatval(bcmul(bcmul((620 - 35), .8, 4), 1.05, 4)), $total);
    }
}