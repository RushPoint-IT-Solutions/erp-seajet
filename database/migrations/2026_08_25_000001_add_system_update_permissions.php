<?php

use App\Services\Security\RbacProvisioner;
use Illuminate\Database\Migrations\Migration;

class AddSystemUpdatePermissions extends Migration
{
    public function up()
    {
        app(RbacProvisioner::class)->provision();
    }

    public function down()
    {
        // Permission configuration is retained to preserve auditability.
    }
}
