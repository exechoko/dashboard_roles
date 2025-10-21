<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpnFieldToPasswordVaultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('password_vaults', function (Blueprint $table) {
            $table->string('vpn_type')->nullable()->after('system_type');
            $table->string('vpn_host')->nullable()->after('vpn_type');
            $table->text('vpn_preshared_key')->nullable()->after('vpn_host');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('password_vaults', function (Blueprint $table) {
            $table->dropColumn(['vpn_type', 'vpn_host', 'preshared_key']);
        });
    }
}
