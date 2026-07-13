<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('armas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('arma_tipo_id')->nullable()->constrained('arma_tipos')->nullOnDelete();
            $table->string('origen', 50)->default('manual');
            $table->timestamps();
        });

        Schema::create('chalecos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_serie', 50)->unique();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('talle', 20)->nullable();
            $table->string('nivel', 50)->nullable();
            $table->string('lote', 50)->nullable();
            $table->string('origen', 50)->default('manual');
            $table->text('observacion_origen')->nullable();
            $table->timestamps();
        });

        Schema::table('personals', function (Blueprint $table) {
            $table->unsignedInteger('personal911_id')->nullable()->unique()->after('id');
        });

        Schema::create('personal_arma_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->cascadeOnDelete();
            $table->foreignId('arma_id')->constrained('armas')->restrictOnDelete();
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->string('origen', 50)->default('manual');
            $table->timestamps();
            $table->index(['personal_id', 'fecha_hasta']);
            $table->index(['arma_id', 'fecha_hasta']);
        });

        Schema::create('personal_chaleco_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->cascadeOnDelete();
            $table->foreignId('chaleco_id')->constrained('chalecos')->restrictOnDelete();
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->string('origen', 50)->default('manual');
            $table->timestamps();
            $table->index(['personal_id', 'fecha_hasta']);
            $table->index(['chaleco_id', 'fecha_hasta']);
        });

        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->foreignId('arma_id')->nullable()->after('personal_id')->constrained('armas')->nullOnDelete();
            $table->foreignId('chaleco_id')->nullable()->after('arma_id')->constrained('chalecos')->nullOnDelete();
        });

        DB::table('personals')
            ->whereNull('deleted_at')
            ->whereNotNull('numeracion_arma')
            ->where('numeracion_arma', '<>', '')
            ->orderBy('id')
            ->each(function (object $personal): void {
                $arma = DB::table('armas')->where('numero', $personal->numeracion_arma)->first();
                $armaId = $arma?->id ?? DB::table('armas')->insertGetId([
                    'numero' => $personal->numeracion_arma,
                    'arma_tipo_id' => $personal->arma_tipo_id,
                    'origen' => 'manual',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $armaYaAsignada = DB::table('personal_arma_asignaciones')
                    ->where('arma_id', $armaId)
                    ->whereNull('fecha_hasta')
                    ->exists();

                if (!$armaYaAsignada) {
                    DB::table('personal_arma_asignaciones')->insert([
                        'personal_id' => $personal->id,
                        'arma_id' => $armaId,
                        'fecha_desde' => now()->toDateString(),
                        'origen' => 'migracion',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $chalecoId = null;
                if ($personal->nro_chaleco !== null && trim($personal->nro_chaleco) !== '') {
                    $chaleco = DB::table('chalecos')->where('numero_serie', $personal->nro_chaleco)->first();
                    $chalecoId = $chaleco?->id ?? DB::table('chalecos')->insertGetId([
                        'numero_serie' => $personal->nro_chaleco,
                        'origen' => 'manual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $chalecoYaAsignado = DB::table('personal_chaleco_asignaciones')
                        ->where('chaleco_id', $chalecoId)
                        ->whereNull('fecha_hasta')
                        ->exists();

                    if (!$chalecoYaAsignado) {
                        DB::table('personal_chaleco_asignaciones')->insert([
                            'personal_id' => $personal->id,
                            'chaleco_id' => $chalecoId,
                            'fecha_desde' => now()->toDateString(),
                            'origen' => 'migracion',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::table('arma_retenciones')
                    ->where('personal_id', $personal->id)
                    ->update(['arma_id' => $armaId, 'chaleco_id' => $chalecoId]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chaleco_id');
            $table->dropConstrainedForeignId('arma_id');
        });

        Schema::dropIfExists('personal_chaleco_asignaciones');
        Schema::dropIfExists('personal_arma_asignaciones');

        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('personal911_id');
        });

        Schema::dropIfExists('chalecos');
        Schema::dropIfExists('armas');
    }
};
