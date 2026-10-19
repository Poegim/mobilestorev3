<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64);
            $table->string('hex', 7)->nullable();
            $table->timestamps();

            $table->unique('name');
        });

        // Seed basic colors
        $colors = [
            ['name' => 'Czarny', 'hex' => '#000000'],
            ['name' => 'Biały', 'hex' => '#FFFFFF'],
            ['name' => 'Srebrny', 'hex' => '#C0C0C0'],
            ['name' => 'Szary', 'hex' => '#808080'],
            ['name' => 'Złoty', 'hex' => '#FFD700'],
            ['name' => 'Różowy', 'hex' => '#FFC0CB'],
            ['name' => 'Czerwony', 'hex' => '#FF0000'],
            ['name' => 'Niebieski', 'hex' => '#0000FF'],
            ['name' => 'Zielony', 'hex' => '#008000'],
            ['name' => 'Fioletowy', 'hex' => '#800080'],
            ['name' => 'Granatowy', 'hex' => '#000080'],
            ['name' => 'Brązowy', 'hex' => '#8B4513'],
            ['name' => 'Pomarańczowy', 'hex' => '#FF8C00'],
            ['name' => 'Żółty', 'hex' => '#FFD700'],
            ['name' => 'Turkusowy', 'hex' => '#40E0D0'],
            ['name' => 'Beżowy', 'hex' => '#F5F5DC'],
        ];

        $now = now();
        foreach ($colors as &$c) {
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
        }

        DB::table('colors')->insert($colors);
    }

    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};