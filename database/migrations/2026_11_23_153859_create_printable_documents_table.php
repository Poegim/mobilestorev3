<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('printable_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // documentable_type + documentable_id
            $table->string('type'); // 'contract', 'invoice', 'bill' - bo jeden model może mieć kilka dokumentów
            $table->longText('html')->nullable();
            $table->unsignedInteger('ordinal')->nullable();
            $table->timestamps();
            $table->unique(['documentable_type', 'documentable_id', 'type'], 'printable_documents_unique');        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printable_documents');
    }
};
