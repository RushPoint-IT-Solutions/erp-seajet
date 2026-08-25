<?php

use Illuminate\Database\Seeder;

require_once __DIR__.'/SeaJetProductionDataSeeder.php';
require_once __DIR__.'/SeaJetTestDataSeeder.php';
require_once __DIR__.'/BillingReimbursementSampleSeeder.php';
require_once __DIR__.'/RbacSeeder.php';

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(SeaJetProductionDataSeeder::class);

        if (app()->environment('testing') || app()->runningUnitTests()) {
            $this->call(SeaJetTestDataSeeder::class);
            $this->call(BillingReimbursementSampleSeeder::class);
        }

        $this->call(RbacSeeder::class);
    }
}
