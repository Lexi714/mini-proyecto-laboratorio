<?php
/**
 * VISTA: listado de pedidos.
 * Recibe $pedidos (Order[]) ya resuelto por el controlador. Solo muestra datos
 * y escapa toda la salida: sin consultas, sin calculos.
 */
?>
<h1>Pedidos</h1>

<table border="1">
    <tr><th>ID</th><th>Paciente</th><th>Total</th></tr>

    <?php foreach ($pedidos as $pedido): ?>
        <tr>
            <td><?= htmlspecialchars((string) $pedido->id, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($pedido->patient, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $pedido->amount, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    <?php endforeach; ?>
</table>
