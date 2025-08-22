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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->integer('age');
            $table->text('bio');
            $table->string('gender');
            $table->string('looking_for');
            $table->string('relationship_goal');
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('height')->nullable();
            $table->string('religion')->nullable();
            $table->string('smoking')->nullable();
            $table->string('drinking')->nullable();
            $table->boolean('has_children')->default(false);
            $table->string('zodiac_sign')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('last_active')->nullable();
            $table->timestamps();
        });

        // Create hobbies table
        Schema::create('hobbies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create interests table
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create user_hobbies pivot table
        Schema::create('user_hobbies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('hobby_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Create user_interests pivot table
        Schema::create('user_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('interest_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Create user_photos table
        Schema::create('user_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('photo_url');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Create matches table
        Schema::create('user_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user2_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_mutual')->default(false);
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();
        });

        // Create likes table
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('liked_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create conversations table
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user2_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        // Create messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('user_matches');
        Schema::dropIfExists('user_photos');
        Schema::dropIfExists('user_interests');
        Schema::dropIfExists('user_hobbies');
        Schema::dropIfExists('interests');
        Schema::dropIfExists('hobbies');
        Schema::dropIfExists('users');
    }
};
