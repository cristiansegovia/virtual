<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #{{ $factura->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; color: #222; }
        .container { max-width: 800px; margin: 0 auto; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 24px; }
        .customer, .meta { margin-bottom: 16px; }
        .meta span { display: inline-block; min-width: 160px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f3f3f3; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; }
        .footer { margin-top: 32px; font-size: 11px; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Factura</h1>
                <p>Nro: {{ $factura->invoice_number }}</p>
            </div>
            <div>
                <p><strong>Emitida:</strong> {{ $factura->fecha_emision?->format('d/m/Y') }}</p>
                <p><strong>Vence:</strong> {{ $factura->fecha_vencimiento?->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="customer">
            <p><strong>Cliente:</strong> {{ $factura->cliente?->nombre }} {{ $factura->cliente?->apellido }}</p>
            <p><strong>DNI:</strong> {{ $factura->cliente?->dni }}</p>
            <p><strong>Periodo:</strong> {{ ucfirst($factura->periodo) }}</p>
            <p><strong>Estado:</strong> {{ ucfirst($factura->estado) }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->planes as $plan)
                    <tr>
                        <td>{{ $plan->nombre }}</td>
                        <td class="text-right">${{ number_format($plan->valor, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total</td>
                    <td class="text-right">${{ number_format($factura->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($factura->detalle)
            <div class="footer">
                <p><strong>Detalle:</strong></p>
                <p>{{ $factura->detalle }}</p>
            </div>
        @endif
    </div>
</body>
</html>
