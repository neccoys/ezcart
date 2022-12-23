<?php


namespace Neccoys\EzCart\Tests;


use Neccoys\EzCart\Coupons\Fixed;
use Neccoys\EzCart\Coupons\Percentage;
use Neccoys\EzCart\Helpers\EzCartHelper;
use Orchestra\Testbench\TestCase;

class CouponTest extends TestCase
{
    use EzCartTestTrait;

    public function testAddCoupons()
    {
        $couponPercentage = new Percentage("coupon001", .85,  [
            'description' => "test percent",
            'minAmount' => 10,
            'maxDiscount' => 20
        ]);

        $this->ezcart->addCoupons($couponPercentage);

        $coupon = array_pop($this->ezcart->cart->coupons);

        $this->assertInstanceOf(Percentage::class ,$coupon);
        $this->assertEquals('coupon001' ,$coupon->code);
        $this->assertEquals(10 ,$coupon->minAmount);

        $this->ezcart->cart->coupons = [];
    }

    public function testGetCoupon()
    {
        $couponFixed = new Fixed("coupon002", 35,  [
            'description' => "test fixed coupon",
            'minAmount' => 45
        ]);

        $this->ezcart->addCoupons($couponFixed);

        $coupon = $this->ezcart->getCoupon('coupon002');

        $this->assertInstanceOf(Fixed::class ,$coupon);
        $this->assertEquals('coupon002' ,$coupon->code);
        $this->assertEquals(45 ,$coupon->minAmount);

        $this->ezcart->cart->coupons = [];
    }

    public function testRemoveCoupon()
    {
        $couponFixed = new Fixed("coupon002", 35,  [
            'description' => "test fixed coupon",
            'minAmount' => 45
        ]);

        $this->ezcart->addCoupons($couponFixed);
        $this->assertNotNull($this->ezcart->getCoupon('coupon002'));

        $this->ezcart->removeCoupon('coupon002');
        $this->assertNull($this->ezcart->getCoupon('coupon002'));
    }

    public function testFixedCouponDiscount()
    {
        $this->addItem1(1, 100);

        $couponFixed = new Fixed("coupon002", 35,  [
            'description' => "test fixed coupon",
            'minAmount' => 45,
        ]);

        $this->ezcart->addCoupons($couponFixed);
        $total = $this->ezcart->itemsCouponTotals(false);

        $this->assertEquals(65.0, $total);

    }

    public function testPercentageCouponDiscount()
    {
        $this->ezcart->removeCoupons();

        $this->addItem1(1, 100);

        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 0,
            'maxDiscount' => 0
        ]);

        $this->ezcart->addCoupons($couponPercentage);


        $total = $this->ezcart->itemsCouponTotals(false);

        $this->assertEquals(80.0, $total);

        $this->ezcart->removeCoupons();

        // test minAmount
        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 200,
            'maxDiscount' => 0
        ]);

        $this->ezcart->addCoupons($couponPercentage);

        $total = $this->ezcart->itemsCouponTotals(false);

        $this->assertEquals(100.0, $total);

        $this->ezcart->removeCoupons();

        // test maxDiscount
        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 0,
            'maxDiscount' => 10
        ]);

        $this->ezcart->addCoupons($couponPercentage);

        $total = $this->ezcart->itemsCouponTotals(false);

        $this->assertEquals(90.0, $total);

    }

    public function testCalculateAllCouponTotal()
    {
        $this->addItem1(3, 100);
        $this->ezcart->cart->multipleCoupons = true;

        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 0,
        ]);

        $this->ezcart->addCoupons($couponPercentage);

        $this->assertEquals(3*100*.8, $this->ezcart->itemsCouponTotals(false));


        $couponFixed = new Fixed("coupon002", 35,  [
            'description' => "test fixed coupon",
            'minAmount' => 45,
        ]);

        $this->ezcart->addCoupons($couponFixed);

        $this->assertEquals((3*100-35)*0.8, $this->ezcart->itemsCouponTotals(false));
    }

    public function testGetCouponsDiscount()
    {
        $this->addItem1(3, 100);
        $item2 = $this->addItem2(2, 160);

        $item2->coupon(false);

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

        // $this->ezcart->itemsCouponTotals(false);

        $this->ezcart->calculateCouponTotal();

        $this->assertEquals(35+((300-35)*0.2), $this->ezcart->getCouponsDiscount());
    }

    public function testResetCouponsDiscount()
    {
        $this->addItem1(3, 101);
        $this->ezcart->cart->multipleCoupons = true;

        $couponPercentage = new Percentage("coupon001", .8,  [
            'description' => "test percent",
            'minAmount' => 0,
        ]);

        $this->ezcart->addCoupons($couponPercentage);
        $this->ezcart->itemsCouponTotals(false);

        $this->assertEquals(60.6, $this->ezcart->getCouponsDiscount());
        $this->ezcart->resetCouponsDiscount();
        $this->assertEquals(0, $this->ezcart->getCouponsDiscount());
    }

}