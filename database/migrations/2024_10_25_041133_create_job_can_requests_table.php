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
        Schema::create('job_can_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique()->index('job_can_requests_id');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('form_id')->nullable();
            $table->string('form_name')->nullable();
            $table->string('form_type')->nullable();
            $table->string('settlement_type')->nullable();
            $table->string('status');
            $table->dateTime('applied_date')->nullable();
            $table->string('applicant_code')->nullable();
            $table->string('applicant_last_name')->nullable();
            $table->string('applicant_first_name')->nullable();
            $table->string('applicant_group_name')->nullable();
            $table->string('applicant_position_name')->nullable();
            $table->string('proxy_applicant_last_name')->nullable();
            $table->string('proxy_applicant_first_name')->nullable();
            $table->string('group_name')->nullable();
            $table->string('group_code')->nullable();
            $table->string('project_name')->nullable();
            $table->string('project_code')->nullable();
            $table->string('flow_step_name')->nullable();
            $table->boolean('is_content_changed')->default(false)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->dateTime('pay_at')->nullable();
            $table->dateTime('final_approval_period')->nullable();
            $table->dateTime('final_approved_date')->nullable();
            $table->string('applicant_group_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_can_requests');
    }
};
