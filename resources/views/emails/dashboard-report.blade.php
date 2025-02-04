<!DOCTYPE html>
<html>
<head>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Reporte del Dashboard - {{ $fecha }}</h2>

    <h3>Resumen de Ventas</h3>
    <table>
        <tr>
            <th>Monto Total</th>
            <td>S/. {{ number_format($data['total']['montoTotal'], 2) }}</td>
        </tr>
        <tr>
            <th>Documentos Emitidos</th>
            <td>{{ $data['total']['cantidadDocumentos'] }}</td>
        </tr>
    </table>

    <p>Se adjunta el reporte detallado en Excel.</p>
</body>
</html>
