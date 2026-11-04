<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Kenyan CBC 4-Level Performance Scale"
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        Schema::create('grading_scale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scale_id')->constrained('grading_scales')->cascadeOnDelete();
            $table->tinyInteger('level'); // 4, 3, 2, 1
            $table->string('code', 10); // EE, ME, AE, BE
            $table->string('name'); // Exceeding Expectations, Meeting Expectations, Approaching Expectations, Below Expectations
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->text('descriptor')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Opener Assessment, Mid-Term Assessment, End-Term Summative Assessment
            $table->string('code')->unique(); // OPEN, MID, END
            $table->decimal('weight', 5, 2)->default(100.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assessment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('learning_area_id')->constrained('learning_areas')->cascadeOnDelete();
            $table->foreignId('assessment_type_id')->constrained('assessment_types')->cascadeOnDelete();
            $table->decimal('raw_score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->string('performance_level', 10)->nullable(); // EE, ME, AE, BE
            $table->text('rubric_comments')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['learner_id', 'academic_year_id', 'term_id', 'learning_area_id', 'assessment_type_id'], 'uniq_learner_assessment');
        });

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->integer('attendance_days_present')->default(0);
            $table->integer('attendance_days_absent')->default(0);
            $table->integer('attendance_days_total')->default(0);
            $table->text('class_teacher_remarks')->nullable();
            $table->text('headteacher_remarks')->nullable();
            $table->date('next_term_begins')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->unique(['learner_id', 'academic_year_id', 'term_id'], 'uniq_learner_report_card');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('assessment_records');
        Schema::dropIfExists('assessment_types');
        Schema::dropIfExists('grading_scale_details');
        Schema::dropIfExists('grading_scales');
    }
};

