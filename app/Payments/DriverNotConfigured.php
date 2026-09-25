<?php

namespace App\Payments;

/*
 * DDE-Mart Admin — thrown when a gateway lacks credentials (original exception).
 */
class DriverNotConfigured extends \RuntimeException {}
