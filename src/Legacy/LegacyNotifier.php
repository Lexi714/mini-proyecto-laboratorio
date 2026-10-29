<?php
/**
 * ============================================================================
 *  SISTEMA HEREDADO DEL LABORATORIO (CODIGO DE TERCEROS)
 * ============================================================================
 *
 *  Sistema de avisos hecho por otro proveedor. NO SE MODIFICA: lo usan tambien
 *  Recepcion y Facturacion. Para usarlo desde nuestro sistema se pasa siempre
 *  por LegacyNotifierAdapter (ver src/Legacy/LegacyNotifierAdapter.php).
 * ============================================================================
 */

class LegacyNotifier
{
    /** Metodo original del proveedor. No lo podemos cambiar. */
    public function sendMessage(string $text): void
    {
        echo "[LEGACY] {$text}<br>";
    }
}
