<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Busca por texto (`title ILIKE`/`description ILIKE` em tasks, `name`/
     * `email ILIKE` em admin.users) nunca usa um índice B-tree comum —
     * precisa de um índice GIN com `pg_trgm` pra deixar de fazer sequential
     * scan assim que o volume crescer.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('CREATE INDEX tasks_title_trgm_index ON public.tasks USING GIN (title gin_trgm_ops)');
        DB::statement('CREATE INDEX tasks_description_trgm_index ON public.tasks USING GIN (description gin_trgm_ops)');

        DB::statement('CREATE INDEX users_name_trgm_index ON admin.users USING GIN (name gin_trgm_ops)');
        DB::statement('CREATE INDEX users_email_trgm_index ON admin.users USING GIN (email gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS public.tasks_title_trgm_index');
        DB::statement('DROP INDEX IF EXISTS public.tasks_description_trgm_index');
        DB::statement('DROP INDEX IF EXISTS admin.users_name_trgm_index');
        DB::statement('DROP INDEX IF EXISTS admin.users_email_trgm_index');
    }
};
