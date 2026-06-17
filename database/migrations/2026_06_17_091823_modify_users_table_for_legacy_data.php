<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // stare kolumny potrzebne do importu
            $table->unsignedInteger('contact_id')->default(0)->after('id');
            $table->unsignedTinyInteger('privilege')->default(0)->after('contact_id');
            $table->string('login', 48)->nullable()->unique()->after('privilege');
            $table->text('data')->nullable()->after('password');

            // starter kit kolumny — zostawiamy ale nullable (stare dane ich nie mają)
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['contact_id', 'privilege', 'login', 'data']);
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
