<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('ID_EMPRESA');
            $table->string('TP_IMPORT');
            $table->string('TP_ARQUIVO');
            $table->string('NM_ARQUIVO');
            $table->string('PASTA');
            $table->string('ANDAMENTO');
            $table->string('STATUS');
            $table->text('RETORNO')->nullable();
            $table->timestamps();
            $table->foreign('ID_EMPRESA')->references('ID')->on('empresa');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imports');
    }
};
