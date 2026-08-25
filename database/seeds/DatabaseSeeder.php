<?php

use Illuminate\Database\Seeder;

require_once __DIR__.'/SeaJetSampleDataSeeder.php';
require_once __DIR__.'/SeaJetTestDataSeeder.php';
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
        $this->call(ErpDemoSeeder::class);
        $this->call(SeaJetSampleDataSeeder::class);
        $this->call(SeaJetTestDataSeeder::class);
        $this->call(RbacSeeder::class);
    }
}
