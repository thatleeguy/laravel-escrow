<?php

namespace Makeable\LaravelEscrow\Tests;

use DB;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Makeable\LaravelEscrow\Contracts\TransactionContract;
use Makeable\LaravelEscrow\EscrowServiceProvider;
use Makeable\LaravelEscrow\Tests\Fakes\Product;
use Makeable\LaravelEscrow\Tests\Fakes\Transaction;
use Makeable\ValueObjects\Amount\Amount;
use Makeable\ValueObjects\Amount\TestCurrency;

class TestCase extends BaseTestCase
{
    /**
     */
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        // Put Amount in test mode so we don't need a currency implementation
        Amount::test();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->register(EscrowServiceProvider::class);
        $app->bind(TransactionContract::class, Transaction::class);

        return $app;
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        DB::connection()->getSchemaBuilder()->create('products', function (Blueprint $table) {
            $table->increments('id');
        });

        DB::connection()->getSchemaBuilder()->create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_type');
            $table->integer('source_id');
            $table->string('destination_type');
            $table->integer('destination_id');
            $table->decimal('amount');
            $table->string('currency');

            $table->index(['source_type', 'source_id']);
            $table->index(['destination_type', 'destination_id']);
        });

        // Create escrows table
        Artisan::call('vendor:publish', ['--tag' => 'migrations', '--provider' => EscrowServiceProvider::class]);
        Artisan::call('migrate');

        $app->make(Factory::class)->define(Product::class, function ($faker) {
            return [];
        });
        $app->make(Factory::class)->define(Transaction::class, function ($faker) {
            return [
                'source_type' => 'foo',
                'source_id' => 1,
                'destination_type' => 'bar',
                'destination_id' => 1,
                'amount' => rand(100, 200),
                'currency' => array_rand(TestCurrency::$currencies)
            ];
        });
    }
}