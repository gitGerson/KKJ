<?php

namespace App\Models;

use Database\Factories\KeluargaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no_keluarga'])]
class Keluarga extends Model
{
    /** @use HasFactory<KeluargaFactory> */
    use HasFactory;

    protected $table = 'keluarga';

    public function umat(): HasMany
    {
        return $this->hasMany(Umat::class);
    }
}
