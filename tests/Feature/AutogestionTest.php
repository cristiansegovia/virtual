<?php

namespace Tests\Feature;

use App\Filament\Pages\Autogestion;
use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AutogestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_autogestion_page_renders_successfully()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/autogestion')
            ->assertSuccessful();

        $response
            ->assertSee('wire:snapshot', false)
            ->assertSee('livewire.js', false)
            ->assertSee('wire:click="appendDigit', false);
    }

    public function test_keypad_appends_and_deletes_digits()
    {
        Livewire::actingAs($this->user)
            ->test(Autogestion::class)
            ->call('appendDigit', '3')
            ->call('appendDigit', '8')
            ->call('appendDigit', '1')
            ->assertSet('dni', '381')
            ->call('deleteDigit')
            ->assertSet('dni', '38')
            ->call('clearDni')
            ->assertSet('dni', '');
    }

    public function test_nonexistent_dni_shows_error()
    {
        Livewire::actingAs($this->user)
            ->test(Autogestion::class)
            ->set('dni', '99999999')
            ->call('buscarCliente')
            ->assertSet('paso', 'error')
            ->assertSet('mensaje_error', 'Cliente no encontrado');
    }

    public function test_client_with_single_plan_and_valid_invoice_registers_attendance_directly()
    {
        $plan = Plan::create([
            'nombre' => 'Plan Test Único',
            'categoria' => 'Funcional',
            'valor' => 10000,
            'periodo' => 'Mensual',
            'contador' => 10,
            'estado' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => '12345678',
            'estado' => true,
            'fecha_de_ingreso' => now(),
        ]);
        $cliente->planes()->attach($plan->id);

        $factura = Factura::create([
            'cliente_id' => $cliente->id,
            'periodo' => 'mensual',
            'estado' => 'pagada',
            'fecha_emision' => now(),
            'total' => 10000,
        ]);
        $factura->planes()->attach($plan->id);

        Livewire::actingAs($this->user)
            ->test(Autogestion::class)
            ->set('dni', '12345678')
            ->call('buscarCliente')
            ->assertSet('paso', 'exito');

        $this->assertDatabaseHas('asistencias', [
            'id_cliente' => $cliente->id,
            'id_plan' => $plan->id,
            'id_factura' => $factura->id,
            'origen' => 'autogestion',
            'contador_asistencias' => 9,
            'clases_consumidas' => 1,
        ]);
    }

    public function test_client_with_multiple_plans_shows_plan_selection_and_registers_selected_plan()
    {
        $planA = Plan::create([
            'nombre' => 'Funcional Test',
            'categoria' => 'Funcional',
            'valor' => 10000,
            'periodo' => 'Mensual',
            'contador' => 20,
            'estado' => true,
        ]);

        $planB = Plan::create([
            'nombre' => 'Boxeo Test',
            'categoria' => 'Tecnica de Boxeo',
            'valor' => 15000,
            'periodo' => 'Mensual',
            'contador' => 12,
            'estado' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Maria',
            'apellido' => 'Gomez',
            'dni' => '87654321',
            'estado' => true,
            'fecha_de_ingreso' => now(),
        ]);
        $cliente->planes()->attach([$planA->id, $planB->id]);

        $factura = Factura::create([
            'cliente_id' => $cliente->id,
            'periodo' => 'mensual',
            'estado' => 'vigente',
            'fecha_emision' => now(),
            'total' => 25000,
        ]);
        $factura->planes()->attach([$planA->id, $planB->id]);

        $test = Livewire::actingAs($this->user)
            ->test(Autogestion::class)
            ->set('dni', '87654321')
            ->call('buscarCliente')
            ->assertSet('paso', 'seleccion_plan');

        $test->call('selectPlan', $planB->id)
            ->assertSet('paso', 'exito');

        $this->assertDatabaseHas('asistencias', [
            'id_cliente' => $cliente->id,
            'id_plan' => $planB->id,
            'id_factura' => $factura->id,
            'origen' => 'autogestion',
            'contador_asistencias' => 11,
            'clases_consumidas' => 1,
        ]);
    }
}
