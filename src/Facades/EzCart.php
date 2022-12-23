<?php


namespace Neccoys\EzCart\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * Class EzCart
 */
class EzCart extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ezcart';
    }
}
