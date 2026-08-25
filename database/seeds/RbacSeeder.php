<?php

use App\Services\Security\RbacProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    public function run()
    {
        app(RbacProvisioner::class)->provision(DB::table('companies')->pluck('id')->all());
    }
}
