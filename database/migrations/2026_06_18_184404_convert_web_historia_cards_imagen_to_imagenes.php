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
        Schema::table('web_historia_cards', function (Blueprint $table) {
            $table->json('imagenes')->nullable()->after('tag');
        });

        DB::table('web_historia_cards')
            ->whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->orderBy('id')
            ->each(function (object $card): void {
                DB::table('web_historia_cards')
                    ->where('id', $card->id)
                    ->update(['imagenes' => json_encode([$card->imagen])]);
            });

        Schema::table('web_historia_cards', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_historia_cards', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('tag');
        });

        DB::table('web_historia_cards')
            ->whereNotNull('imagenes')
            ->orderBy('id')
            ->each(function (object $card): void {
                $imagenes = json_decode($card->imagenes ?? '[]', true);
                DB::table('web_historia_cards')
                    ->where('id', $card->id)
                    ->update(['imagen' => $imagenes[0] ?? null]);
            });

        Schema::table('web_historia_cards', function (Blueprint $table) {
            $table->dropColumn('imagenes');
        });
    }
};
