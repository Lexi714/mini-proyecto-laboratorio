<?php
/**
 * Contrato de quien quiere enterarse de que se creo un pedido.
 * El sujeto conoce esta interfaz, no las clases concretas.
 */
interface OrderObserver
{
    public function update(Order $order): void;
}
