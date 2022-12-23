<?php

namespace Neccoys\EzCart\Tests;


use Neccoys\EzCart\EzCart;

trait EzCartTestTrait
{
    public $ezcart;

    public function setUp(): void
    {
        parent::setUp();

        $this->ezcart = new EzCart($this->session, $this->events, $this->authManager);
    }

    protected function getEnvironmentSetUp($app)
    {
        $this->session = $app['session'];
        $this->events = $app['events'];
        $this->authManager = $app['auth'];

        $app['config']->set('database.default', 'testing');

        $app['config']->set('ezcart.tax', .05);
        $app['config']->set('ezcart.prices_scale', 4);
    }

    protected function getPackageProviders($app)
    {
        return ['\Neccoys\EzCart\EzCartServiceProvider'];
    }

    private function addItem1($qty = 1, $price = 10, $options = [], $lineItem = false)
    {
        if (empty($options)) {
            $options = [
                'a_test' => 'aaaa',
                'b_test' => 'bbb',
            ];
        }

        return $this->ezcart->add(
            'item001',
            'product001',
            $qty,
            $price,
            $options,
            $lineItem
        );
    }

    private function addItem2($qty = 1, $price = 10, $options = [], $lineItem = false)
    {
        if (empty($options)) {
            $options = [
                'b_test' => 'bbb',
            ];
        }

        return $this->ezcart->add(
            'item002',
            'product002',
            $qty,
            $price,
            $options,
            $lineItem
        );
    }
}