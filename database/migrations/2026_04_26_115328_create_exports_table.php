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
        Schema::create('exports', function (Blueprint $table) {
       $table->id();
     $table->string('export_id')->unique(); // أضف هذا السطر لأنه العمود المستخدم في الكود للبحث
     $table->foreignId('user_id')->constrained()->onDelete('cascade');
     $table->string('type')->default('users'); 
     $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
     $table->integer('records_count')->default(0); // مفيد لمتابعة عدد السجلات المصدرة
     $table->string('file_path')->nullable();
     $table->text('error_message')->nullable();
     $table->timestamp('completed_at')->nullable(); // لتتبع وقت الانتهاء بدقة
     $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
