<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('material_table', 64);
            $table->unsignedBigInteger('material_id');
            $table->string('material_kind', 32)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_batch', 64);
            $table->string('action', 20)->default('updated');
            $table->json('changes');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->timestamp('edited_at');
            $table->timestamps();

            $table->index(['material_table', 'material_id', 'edited_at']);
            $table->unique(['material_table', 'material_id', 'request_batch', 'action'], 'material_change_logs_request_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_change_logs');
    }
};
