<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->tinyInteger('term_number'); // 1, 2, 3
            $table->string('name'); // e.g. "Term 1"
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_term_begins')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->unique(['academic_year_id', 'term_number']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Grade 1", "Grade 2", ..., "Grade 7"
            $table->string('code')->unique(); // "G1", "G2", "G7"
            $table->tinyInteger('level')->unique(); // 1..9
            $table->string('stage'); // "Lower Primary", "Upper Primary", "Junior School"
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->string('name'); // "Red", "Blue", "Green", "A", "B"
            $table->integer('capacity')->default(40);
            $table->timestamps();
            $table->unique(['grade_id', 'name']);
        });

        Schema::create('learning_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Mathematics", "English Language", "Kiswahili Language", etc.
            $table->string('code')->unique(); // "MATH", "ENG", "KISW"
            $table->string('stage')->default('All'); // "Lower Primary", "Upper Primary", "Junior School", "All"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tsc_number')->nullable()->unique();
            $table->string('national_id')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('designation')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->timestamps();
        });

        Schema::create('class_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['academic_year_id', 'stream_id']);
        });

        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('learning_area_id')->constrained('learning_areas')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['academic_year_id', 'stream_id', 'learning_area_id', 'teacher_id'], 'uniq_tch_assign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('class_teachers');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('learning_areas');
        Schema::dropIfExists('streams');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
    }
};

