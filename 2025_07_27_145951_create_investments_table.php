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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('investment_plan_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('profit_amount', 15, 2);
            $table->decimal('total_return', 15, 2);
            $table->enum('status', ['active', 'matured', 'cancelled'])->default('active');
            $table->timestamp('maturity_date');
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('investment_plan_id')->references('id')->on('investment_plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
