<?php
namespace App\Exceptions;

// A business rejection safe to display; SQL/internal failures keep their existing handling.
class TenantCapacityException extends \RuntimeException {}
