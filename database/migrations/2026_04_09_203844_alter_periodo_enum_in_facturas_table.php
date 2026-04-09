<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE facturas MODIFY COLUMN periodo ENUM('diario', 'mensual', 'trimestral', 'semestral', 'anual', 'pase libre') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE facturas MODIFY COLUMN periodo ENUM('mensual', 'trimestral', 'semestral', 'anual', 'pase libre') NOT NULL");
    }
};
