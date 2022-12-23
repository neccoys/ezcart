<?php


namespace Neccoys\EzCart;


use Illuminate\Support\ServiceProvider;

class EzCartServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Publications/config/ezcart.php' => config_path('ezcart.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__.'/Publications/config/ezcart.php',
            'ezcart'
        );

        if (!$this->migrationHasAlreadyBeenPublished()) {
            $this->publishes([
                __DIR__.'/Publications/database/migrations/add_cart_session_id_to_users_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_add_cart_session_id_to_users_table.php'),
            ], 'migrations');
        }
    }

    public function register()
    {
        $this->app->singleton(EzCart::SERVICE, function ($app) {
            return new EzCart($app['session'], $app['events'], $app['auth']);
        });
    }

    /**
     * Checks to see if the migration has already been published.
     *
     * @return bool
     */
    protected function migrationHasAlreadyBeenPublished()
    {
        $files = glob(database_path('Publications/migrations/*_add_cart_session_id_to_users_table.php'));

        return count($files) > 0;
    }

}
