<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — Currency model (original). Exactly one default currency.
 */
class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    protected $fillable = [
        'code', 'name', 'symbol', 'decimals', 'symbol_at_right', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return ['symbol_at_right' => 'boolean', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = [
        'decimals' => 2,
        'symbol_at_right' => false,
        'is_default' => false,
        'is_active' => true,
    ];

    public function format(float $amount): string
    {
        $number = number_format($amount, $this->decimals);

        return $this->symbol_at_right ? "{$number} {$this->symbol}" : "{$this->symbol}{$number}";
    }
}
