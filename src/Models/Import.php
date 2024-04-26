<?php

namespace fabrizioquadro\importarxml\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;
    protected $table = "imports";

    protected $fillable = [
        'ID_EMPRESA',
        'TP_IMPORT',
        'TP_ARQUIVO',
        'NM_ARQUIVO',
        'PASTA',
        'ANDAMENTO',
        'STATUS',
        'RETORNO',
    ];
}
