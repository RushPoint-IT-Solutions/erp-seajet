<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\Security\RbacProvisioner;

class SeedDynamicRbacRoles extends Migration
{
    public function up()
    {
        app(RbacProvisioner::class)->provision();
    }

    public function down()
    {
        // Roles and grants are business configuration and are intentionally retained.
    }
}
