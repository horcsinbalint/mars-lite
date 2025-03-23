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
        Schema::dropIfExists('wifi_connections');
        Schema::dropIfExists('localization_contributions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('wifi_connections', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 15);
            $table->string('mac_address', 17);
            $table->string('wifi_username');
            $table->datetime('lease_start')->nullable();
            $table->datetime('lease_end')->nullable();
            $table->datetime('radius_timestamp')->nullable();
            $table->string('note');
        });
        Schema::create('localization_contributions', function (Blueprint $table) {
            $table->id();
            $table->string('language');
            $table->string('key');
            $table->text('value');
            $table->integer('contributor_id')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->boolean('approved');
            $table->timestamps();
        });
    }
};
