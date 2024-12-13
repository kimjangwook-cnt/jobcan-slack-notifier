<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ssl_infos', function (Blueprint $table) {
            $table->id();
            $table->boolean('success');
            $table->string('error')->nullable();
            $table->string('company_name');
            $table->string('domain');
            $table->string('site_name');
            $table->string('issued_to')->nullable();
            $table->string('issued_by')->nullable();
            $table->string('valid_from')->nullable();
            $table->string('valid_to')->nullable();
            $table->string('days_left')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_infos');
    }
};
