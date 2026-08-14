<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AddIndexesToStandardAuthAccountsTable extends Migration
{
    public function up()
    {
        $prefix = Capsule::connection()->getTablePrefix();

        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($prefix . 'standard_auth_accounts');

        if (!$doctrineTable->hasIndex('standard_auth_accounts_login_index')) {
            Capsule::schema()->table('standard_auth_accounts', function (Blueprint $table) {
                $table->index(['Login', 'IsDisabled'], 'standard_auth_accounts_login_index');
            });
        }

        if (!$doctrineTable->hasIndex('standard_auth_accounts_iduser_index')) {
            Capsule::schema()->table('standard_auth_accounts', function (Blueprint $table) {
                $table->index('IdUser', 'standard_auth_accounts_iduser_index');
            });
        }
    }

    public function down()
    {
        $prefix = Capsule::connection()->getTablePrefix();

        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($prefix . 'standard_auth_accounts');

        if ($doctrineTable->hasIndex('standard_auth_accounts_login_index')) {
            Capsule::schema()->table('standard_auth_accounts', function (Blueprint $table) {
                $table->dropIndex('standard_auth_accounts_login_index');
            });
        }

        if ($doctrineTable->hasIndex('standard_auth_accounts_iduser_index')) {
            Capsule::schema()->table('standard_auth_accounts', function (Blueprint $table) {
                $table->dropIndex('standard_auth_accounts_iduser_index');
            });
        }
    }
}
