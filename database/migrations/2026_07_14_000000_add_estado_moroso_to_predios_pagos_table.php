<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstadoMorosoToPrediosPagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('predios_pagos', function (Blueprint $table) {
            $table->integer('estado_moroso')->default(0)->nullable();
            $table->date('fecha_asignacion_moroso')->nullable();
            $table->date('fecha_notificacion_moroso')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('predios_pagos', function (Blueprint $table) {
            $table->dropColumn('estado_moroso');
            $table->dropColumn('fecha_asignacion_moroso');
            $table->dropColumn('fecha_notificacion_moroso');
        });
    }
}
