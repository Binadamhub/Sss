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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2); // 10% withdrawal fee
            $table->decimal('net_amount', 15, 2); // Amount after fee
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->text('admin_comment')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable(); // Admin who processed
            $table->boolean('recommit_required')->default(true);
            $table->boolean('recommit_completed')->default(false);
            $table->decimal('recommit_amount', 15, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
