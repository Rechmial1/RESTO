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
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('endpoint')->unique();
            $table->text('public_key')->nullable();
            $table->text('auth_token')->nullable();
            $table->timestamps();
        });

        Schema::table('global_settings', function (Blueprint $table) {
            $table->string('vapid_public_key')->nullable();
            $table->string('vapid_private_key')->nullable();
            $table->string('vapid_subject')->default('mailto:admin@example.com');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade')->onUpdate('cascade');
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn('vapid_public_key');
            $table->dropColumn('vapid_private_key');
            $table->dropColumn('vapid_subject');
        });
        Schema::dropIfExists('notifications');
    }
};
