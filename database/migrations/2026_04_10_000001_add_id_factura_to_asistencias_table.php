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
        Schema::table('asistencias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_factura')->nullable()->after('id_cliente');
            $table->foreign('id_factura')->references('id')->on('facturas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['id_factura']);
            $table->dropColumn('id_factura');
        });
    }
};
