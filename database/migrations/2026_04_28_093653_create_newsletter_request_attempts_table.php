<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kept for deployments that may already have this migration in their history.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The table is managed by the follow-up migration that runs after newsletter_requests exists.
    }
};
