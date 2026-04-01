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
        Schema::table('facturas', function (Blueprint $table) {
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->enum('periodo', ['mensual', 'trimestral', 'semestral', 'anual', 'pase libre']);
            $table->enum('estado', ['vigente', 'vencida', 'pagada', 'cancelada'])->default('vigente');
            $table->text('detalle')->nullable();
            $table->unique(['cliente_id', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropUnique(['cliente_id', 'periodo']);
            $table->dropColumn(['cliente_id', 'periodo', 'estado', 'detalle']);
        });
    }
};
