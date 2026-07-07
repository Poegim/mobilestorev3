<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Denormalized feature columns missing from the original create migration
            $table->unsignedInteger('feature_color_id')->nullable()->after('feature_price');
            $table->char('feature_imei', 32)->nullable()->after('feature_color_id');
            $table->unsignedInteger('feature_simlock_network_id')->nullable()->after('feature_imei');
            $table->float('feature_memory')->nullable()->after('feature_simlock_network_id');   // RAM in GB
            $table->float('feature_storage')->nullable()->after('feature_memory');               // storage in GB
            $table->boolean('feature_box')->nullable()->after('feature_storage');
            $table->boolean('feature_proof_of_purchase')->nullable()->after('feature_box');
            $table->text('feature_comment')->nullable()->after('feature_proof_of_purchase');
            $table->boolean('feature_dual_sim')->default(false)->after('feature_comment');

            // Index for IMEI lookups (warehouse + sales search)
            $table->index('feature_imei');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['feature_imei']);
            $table->dropColumn([
                'feature_color_id', 'feature_imei', 'feature_simlock_network_id',
                'feature_memory', 'feature_storage', 'feature_box',
                'feature_proof_of_purchase', 'feature_comment', 'feature_dual_sim',
            ]);
        });
    }
};
