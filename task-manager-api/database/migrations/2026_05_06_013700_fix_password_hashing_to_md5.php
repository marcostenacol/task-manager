<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove a função SHA-256 e sua auxiliar que foram criadas durante o debug
        DB::statement('DROP FUNCTION IF EXISTS admin.check_hash_constant_time(text, text)');

        // Restaura a função de geração de hash para o modelo original (Blowfish)
        DB::statement('DROP FUNCTION IF EXISTS admin.generate_password_hash(text)');
        DB::statement("CREATE OR REPLACE FUNCTION admin.generate_password_hash(password text) returns text
                        language plpgsql
                    as
                    $$
                    begin
                        -- O algoritmo 'bf' (Blowfish) é suportado nativamente na imagem Debian do PostgreSQL
                        return crypt(password, gen_salt('bf'))::text;
                    end;
                    $$;");

        // Re-hash admin user com o algoritmo restaurado
        DB::statement("UPDATE admin.users SET password = admin.generate_password_hash('password') WHERE email = 'bteles@example.com'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
