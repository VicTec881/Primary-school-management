<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learners', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number')->unique()->index();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender', 10); // male, female, other
            $table->date('dob');
            $table->date('admission_date');
            $table->string('nemis_number')->nullable()->unique();
            $table->string('assessment_number')->nullable()->unique();
            $table->string('previous_school')->nullable();
            $table->string('status')->default('active'); // active, transferred, graduated, withdrawn, suspended
            $table->string('photo_path')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('special_needs')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id')->nullable()->index();
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->integer('emergency_priority')->default(1);
            $table->timestamps();
        });

        Schema::create('guardian_learner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->string('relationship'); // Father, Mother, Guardian, Sponsor, Other
            $table->boolean('is_primary')->default(false);
            $table->boolean('authorized_to_collect')->default(true);
            $table->timestamps();
            $table->unique(['guardian_id', 'learner_id']);
        });

        Schema::create('learner_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->string('roll_number')->nullable();
            $table->string('status')->default('enrolled'); // enrolled, promoted, repeated, transferred
            $table->date('date_enrolled');
            $table->timestamps();
            $table->unique(['learner_id', 'academic_year_id']);
        });

        Schema::create('learner_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type'); // birth_certificate, immunization_card, prior_report, other
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_documents');
        Schema::dropIfExists('learner_enrollments');
        Schema::dropIfExists('guardian_learner');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('learners');
    }
};

