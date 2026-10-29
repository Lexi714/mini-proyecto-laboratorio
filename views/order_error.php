<?php
/**
 * VISTA: el pedido no se pudo crear.
 * Recibe $mensaje (string) con el motivo para el usuario.
 */
?>
<h1>Pedido no creado</h1>
<p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
