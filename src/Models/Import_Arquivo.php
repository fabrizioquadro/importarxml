<?php

namespace fabrizioquadro\importarxml\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import_Arquivo extends Model
{
    use HasFactory;

    protected $table = 'imports_arquivos';

    protected $fillable = [
        'ID_IMPORT',
        'ID_EMPRESA',
        'ARQUIVO',
        'STATUS',
        'RETORNO',
    ];
}
