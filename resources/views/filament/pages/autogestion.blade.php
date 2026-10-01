@vite(['resources/css/app.css'])

<div
    x-data="{
        audioCtx: null,
        initAudio() {
            if (!this.audioCtx) {
                this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
        },
        beep(freq = 600, duration = 0.06, type = 'sine') {
            try {
                this.initAudio();
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, this.audioCtx.currentTime);
                gain.gain.setValueAtTime(0.15, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + duration);
            } catch (e) {}
        },
        playSuccess() {
            try {
                this.initAudio();
                [523.25, 659.25, 783.99, 1046.50].forEach((freq, i) => {
                    setTimeout(() => this.beep(freq, 0.18, 'triangle'), i * 90);
                });
            } catch (e) {}
        },
        playError() {
            try {
                this.initAudio();
                [220, 180].forEach((freq, i) => {
                    setTimeout(() => this.beep(freq, 0.2, 'sawtooth'), i * 140);
                });
            } catch (e) {}
        }
    }"
    @keydown.window="
        if ($wire.paso === 'dni') {
            if ($event.key >= '0' && $event.key <= '9') {
                beep(700, 0.04);
                $wire.appendDigit($event.key);
            } else if ($event.key === 'Backspace') {
                beep(450, 0.05);
                $wire.deleteDigit();
            } else if ($event.key === 'Enter') {
                beep(850, 0.08);
                $wire.buscarCliente();
            } else if ($event.key === 'Escape') {
                $wire.clearDni();
            }
        }
    "
    class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-emerald-500 selection:text-white font-sans antialiased relative overflow-x-hidden"
    style="background: radial-gradient(circle at 50% 0%, #0f2b23 0%, #061118 60%, #030712 100%);"
>
    <!-- Background subtle ambient glow -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[500px] bg-emerald-500/10 rounded-full blur-3xl pointer-events-none -z-0"></div>

    <!-- Header / Navbar Kiosko -->
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-emerald-950/60 bg-slate-950/40 backdrop-blur-md relative z-10">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('img/logo-afb.png') }}" alt="Logo" class="h-10 w-auto object-contain drop-shadow-md" onerror="this.style.display='none'">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                    AFB FITNESS
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-medium">Terminal de Ingreso</span>
                </h1>
                <p class="text-xs text-slate-400 font-medium">Sistema de Autogestión de Clientes</p>
            </div>
        </div>

        <!-- Reloj en vivo y botón discreto de salida para administración -->
        <div class="flex items-center space-x-5" x-data="{ time: '', date: '', updateClock() { const now = new Date(); this.time = now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); this.date = now.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'long' }); } }" x-init="updateClock(); setInterval(() => updateClock(), 1000)">
            <div class="text-right hidden sm:block">
                <div class="text-lg font-bold font-mono tracking-wider text-emerald-400" x-text="time"></div>
                <div class="text-xs text-slate-400 capitalize" x-text="date"></div>
            </div>

            <!-- Botón discreto de salida a Admin -->
            <a
                href="{{ url('/admin') }}"
                title="Volver al Panel de Administración"
                class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-slate-500 hover:text-slate-200 hover:bg-slate-800 hover:border-slate-700 transition-all duration-200 shadow-sm"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8 relative z-10">

        {{-- ========================================================================= --}}
        {{-- PASO 1: INGRESO DE DNI Y BOTONERA NUMÉRICA                                --}}
        {{-- ========================================================================= --}}
        @if($paso === 'dni')
            <div class="w-full max-w-lg bg-slate-900/85 border border-emerald-500/20 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-emerald-950/40 backdrop-blur-xl animate-fade-in">

                <div class="text-center mb-5">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mb-3 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11v.5M15 15l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">¡Bienvenido!</h2>
                    <p class="text-sm text-slate-400 mt-1">Ingresa tu número de DNI para registrar tu asistencia</p>
                </div>

                <!-- Display DNI -->
                <div class="mb-4">
                    <div class="relative flex items-center justify-center h-20 bg-slate-950/90 border-2 border-emerald-500/40 rounded-2xl px-4 shadow-inner overflow-hidden">
                        <div class="text-3xl sm:text-4xl font-extrabold font-mono tracking-widest text-emerald-400 flex items-center justify-center">
                            @if(strlen($dni) > 0)
                                <span>{{ number_format((int)$dni, 0, '', '.') }}</span>
                            @else
                                <span class="text-slate-600 font-sans font-medium text-lg sm:text-xl tracking-normal">Ingresa tu DNI</span>
                            @endif
                            <span class="inline-block w-3 h-8 bg-emerald-400 ml-1 animate-pulse"></span>
                        </div>
                    </div>
                </div>

                <!-- Selector de Cantidad de Clases -->
                <div class="flex items-center justify-between bg-slate-950/50 border border-slate-800 rounded-2xl px-4 py-2.5 mb-4">
                    <div class="text-left">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Cantidad de Clases</span>
                        <span class="text-xs text-slate-500">A descontar en este ingreso</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button
                            type="button"
                            wire:click="decrementClases"
                            @click="beep(400, 0.05)"
                            class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-95 text-white font-bold text-lg flex items-center justify-center transition border border-slate-700 shadow-sm"
                        >
                            -
                        </button>
                        <span class="text-xl font-bold font-mono text-emerald-400 w-6 text-center">{{ $cantidad_clases }}</span>
                        <button
                            type="button"
                            wire:click="incrementClases"
                            @click="beep(600, 0.05)"
                            class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-95 text-white font-bold text-lg flex items-center justify-center transition border border-slate-700 shadow-sm"
                        >
                            +
                        </button>
                    </div>
                </div>

                <!-- Botonera Numérica -->
                <div class="grid grid-cols-3 gap-2.5 mb-4">
                    @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $numero)
                        <button
                            type="button"
                            wire:click="appendDigit('{{ $numero }}')"
                            @click="beep(650 + {{ $numero * 20 }}, 0.04)"
                            class="h-14 sm:h-16 rounded-2xl bg-slate-800/80 hover:bg-emerald-600/20 active:bg-emerald-500 active:text-slate-950 active:scale-95 text-white font-extrabold text-2xl sm:text-3xl flex items-center justify-center border border-slate-700/80 hover:border-emerald-500/50 transition-all duration-150 shadow-md"
                        >
                            {{ $numero }}
                        </button>
                    @endforeach

                    <!-- Botón Borrar 1 dígito -->
                    <button
                        type="button"
                        wire:click="deleteDigit"
                        @click="beep(400, 0.05)"
                        class="h-14 sm:h-16 rounded-2xl bg-slate-800/60 hover:bg-amber-500/20 active:scale-95 text-amber-400 font-bold text-sm sm:text-base flex flex-col items-center justify-center border border-slate-700 hover:border-amber-500/40 transition-all shadow-md"
                    >
                        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"></path>
                        </svg>
                        <span>Borrar</span>
                    </button>

                    <!-- Botón 0 -->
                    <button
                        type="button"
                        wire:click="appendDigit('0')"
                        @click="beep(650, 0.04)"
                        class="h-14 sm:h-16 rounded-2xl bg-slate-800/80 hover:bg-emerald-600/20 active:bg-emerald-500 active:text-slate-950 active:scale-95 text-white font-extrabold text-2xl sm:text-3xl flex items-center justify-center border border-slate-700/80 hover:border-emerald-500/50 transition-all duration-150 shadow-md"
                    >
                        0
                    </button>

                    <!-- Botón Limpiar todo -->
                    <button
                        type="button"
                        wire:click="clearDni"
                        @click="beep(350, 0.05)"
                        class="h-14 sm:h-16 rounded-2xl bg-slate-800/60 hover:bg-rose-500/20 active:scale-95 text-rose-400 font-bold text-sm sm:text-base flex flex-col items-center justify-center border border-slate-700 hover:border-rose-500/40 transition-all shadow-md"
                    >
                        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Limpiar</span>
                    </button>
                </div>

                <!-- Botón de Confirmación Principal -->
                <button
                    type="button"
                    wire:click="buscarCliente"
                    @click="beep(850, 0.08)"
                    wire:loading.attr="disabled"
                    class="w-full h-16 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 active:scale-[0.98] text-slate-950 font-black text-xl tracking-wide flex items-center justify-center gap-3 shadow-lg shadow-emerald-500/30 transition-all duration-150 disabled:opacity-50"
                >
                    <span wire:loading.remove>CONTINUAR</span>
                    <span wire:loading.flex class="items-center gap-2">
                        <svg class="animate-spin h-6 w-6 text-slate-950" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Verificando...
                    </span>
                    <svg wire:loading.remove class="w-6 h-6 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        @endif


        {{-- ========================================================================= --}}
        {{-- PASO 2: SELECCIÓN DE PLAN / ACTIVIDAD (Cuando tiene más de 1 plan)         --}}
        {{-- ========================================================================= --}}
        @if($paso === 'seleccion_plan')
            <div class="w-full max-w-2xl bg-slate-900/90 border border-emerald-500/20 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl animate-fade-in">

                <!-- Encabezado Cliente -->
                <div class="flex items-center space-x-4 border-b border-slate-800 pb-5 mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800 border-2 border-emerald-500/40 overflow-hidden flex-shrink-0 flex items-center justify-center">
                        @if($cliente_foto)
                            <img src="{{ asset('storage/' . $cliente_foto) }}" alt="{{ $cliente_nombre }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-2xl font-bold text-emerald-400">{{ strtoupper(substr($cliente_nombre, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Cliente Identificado</div>
                        <h2 class="text-2xl font-black text-white truncate">{{ $cliente_nombre }}</h2>
                        <p class="text-sm text-slate-400">DNI: {{ number_format((int)$dni, 0, '', '.') }}</p>
                    </div>
                </div>

                <div class="mb-5">
                    <h3 class="text-lg font-bold text-white mb-1">Selecciona la actividad a la que ingresas:</h3>
                    <p class="text-xs text-slate-400">Presiona sobre una de tus actividades para registrar el ingreso</p>
                </div>

                <!-- Grilla de Actividades / Planes -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    @foreach($planes_disponibles as $planItem)
                        <button
                            type="button"
                            wire:click="selectPlan({{ $planItem['id'] }})"
                            @click="beep(800, 0.06)"
                            class="text-left p-5 rounded-2xl bg-slate-950/70 border-2 border-slate-800 hover:border-emerald-400 hover:bg-emerald-950/20 active:scale-95 transition-all duration-150 group flex flex-col justify-between shadow-lg"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-300 font-semibold border border-emerald-500/20">
                                        {{ $planItem['categoria'] ?? 'Actividad' }}
                                    </span>
                                    @if($planItem['es_ilimitado'])
                                        <span class="text-xs text-emerald-400 font-bold">Ilimitado</span>
                                    @else
                                        <span class="text-xs text-slate-400 font-mono">{{ $planItem['contador'] }} clases/mes</span>
                                    @endif
                                </div>
                                <h4 class="text-lg font-bold text-white group-hover:text-emerald-400 transition-colors">
                                    {{ $planItem['nombre'] }}
                                </h4>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-semibold text-emerald-400 group-hover:translate-x-1 transition-transform">
                                <span>INGRESAR AQUÍ</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </button>
                    @endforeach
                </div>

                <!-- Botón Volver -->
                <button
                    type="button"
                    wire:click="reiniciar"
                    @click="beep(400, 0.05)"
                    class="w-full py-3.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-sm tracking-wide transition border border-slate-700 flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Cancelar y Volver
                </button>
            </div>
        @endif


        {{-- ========================================================================= --}}
        {{-- PASO 3: PANTALLA DE ÉXITO (Ingreso Registrado)                            --}}
        {{-- ========================================================================= --}}
        @if($paso === 'exito')
            <div
                x-init="playSuccess(); setTimeout(() => $wire.reiniciar(), 5000)"
                class="w-full max-w-lg bg-slate-900/90 border-2 border-emerald-500 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-emerald-500/20 backdrop-blur-xl text-center animate-fade-in relative overflow-hidden"
            >
                <!-- Top Progress Bar (auto-reset countdown) -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-slate-800 overflow-hidden">
                    <div class="h-full bg-emerald-400 animate-shrink" style="animation: shrink 5s linear forwards;"></div>
                </div>

                <!-- Icono de Éxito Grande -->
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-500/20 text-emerald-400 border-2 border-emerald-400 mb-4 shadow-lg shadow-emerald-500/30 animate-bounce">
                    <svg class="w-12 h-12 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h2 class="text-3xl font-black text-white tracking-tight mb-1">¡INGRESO CONFIRMADO!</h2>
                <p class="text-sm text-emerald-400 font-semibold mb-6">Tu asistencia ha sido registrada exitosamente</p>

                <!-- Tarjeta de Detalles del Ingreso -->
                <div class="bg-slate-950/80 border border-emerald-500/20 rounded-2xl p-5 mb-6 text-left space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs text-slate-400 font-medium">Cliente</span>
                        <span class="text-base font-bold text-white">{{ $datos_exito['nombre'] ?? '' }} {{ $datos_exito['apellido'] ?? '' }}</span>
                    </div>

                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs text-slate-400 font-medium">Actividad / Plan</span>
                        <span class="text-sm font-bold text-emerald-400">{{ $datos_exito['plan'] ?? '' }}</span>
                    </div>

                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs text-slate-400 font-medium">Clases en este ingreso</span>
                        <span class="text-sm font-mono font-bold text-white">{{ $datos_exito['clases'] ?? 1 }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400 font-medium">Clases restantes en el plan</span>
                        <span class="text-sm font-mono font-bold px-2.5 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                            {{ $datos_exito['restantes'] ?? '' }}
                        </span>
                    </div>
                </div>

                <div class="text-center text-xs text-slate-400 mb-6 font-medium">
                    🏋️ ¡Que tengas un excelente entrenamiento!
                </div>

                <!-- Botón Finalizar -->
                <button
                    type="button"
                    wire:click="reiniciar"
                    @click="beep(600, 0.05)"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-lg tracking-wide transition shadow-lg shadow-emerald-500/20"
                >
                    LISTO / NUEVO INGRESO
                </button>
            </div>
        @endif


        {{-- ========================================================================= --}}
        {{-- PASO 4: PANTALLA DE ERROR / ALERTA                                        --}}
        {{-- ========================================================================= --}}
        @if($paso === 'error')
            <div
                x-init="playError(); setTimeout(() => $wire.reiniciar(), 6500)"
                class="w-full max-w-lg bg-slate-900/90 border-2 border-rose-500/60 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-rose-500/20 backdrop-blur-xl text-center animate-shake relative overflow-hidden"
            >
                <!-- Top Progress Bar (auto-reset) -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-slate-800 overflow-hidden">
                    <div class="h-full bg-rose-500" style="animation: shrink 6.5s linear forwards;"></div>
                </div>

                <!-- Icono de Error -->
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-rose-500/20 text-rose-400 border-2 border-rose-400 mb-4 shadow-lg shadow-rose-500/30">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>

                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-2">{{ $mensaje_error }}</h2>
                <p class="text-sm text-slate-300 leading-relaxed mb-6 bg-slate-950/60 border border-slate-800 rounded-2xl p-4 font-medium">
                    {{ $subtitulo_error }}
                </p>

                <!-- Botón Reintentar -->
                <button
                    type="button"
                    wire:click="reiniciar"
                    @click="beep(500, 0.05)"
                    class="w-full py-4 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-lg tracking-wide transition border border-slate-700 shadow-md"
                >
                    VOLVER A INTENTAR
                </button>
            </div>
        @endif

    </main>

    <!-- Footer Kiosko -->
    <footer class="w-full px-6 py-3 border-t border-slate-900 bg-slate-950/60 text-center text-xs text-slate-600 font-medium z-10">
        Terminal de Acceso Inteligente &copy; {{ date('Y') }} &bull; AFB Fitness
    </footer>

    <style>
        @keyframes shrink {
            from { width: 100%; }
            to { width: 0%; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-fade-in {
            animation: fadeIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
        .animate-shake {
            animation: shake 0.35s ease-in-out;
        }
    </style>
</div>
