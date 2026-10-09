<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createWithLog('owners', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract('code')->type('varchar(20)');
            $table->unique('code');
        });

        Schema::createWithLog('records', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract('code')->type('varchar(50)');
            $table->unique('code');
        });
    }
};
