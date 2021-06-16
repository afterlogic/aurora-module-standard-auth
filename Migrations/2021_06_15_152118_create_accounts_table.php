<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('accounts', function (Blueprint $table) {
            $table->increments('Id');
            $table->boolean('IsDisabled')->default(false);
            $table->integer('IdUser')->default(0);
            $table->string('Login')->default('');
            $table->string('Password')->default('');
            $table->dateTime('LastModified')->default(date('Y-m-d H:i:s'));
            $table->json('Properties')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('accounts');
    }
}
