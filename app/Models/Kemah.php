<?php

namespace App\Models;

use Database\Factories\KemahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Kemah extends Model
{
    /** @use HasFactory<KemahFactory> */
    use HasFactory;

    protected $table = 'kemah';

    public function umat(): HasMany
    {
        return $this->hasMany(Umat::class);
    }
}
