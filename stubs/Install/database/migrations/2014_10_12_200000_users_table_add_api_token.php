<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UsersTableAddApiToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::table('users', function (Blueprint $table) {
                $table->string('api_token')->nullable()->after('remember_token')
                    ->comment('Use it only when auth:api driver is token (TokenGuard).');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['api_token']);
            });
        });
    }
}
