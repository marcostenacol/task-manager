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
        DB::statement("CREATE OR REPLACE FUNCTION admin.process_refresh(last_refresh_token_p text)
            returns TABLE(data json)
            language plpgsql
        as
        $$
        declare
            old_token_data record;
            new_access_token record;
            new_refresh_token record;
        begin
            -- Busca o user_id associado ao refresh token antigo
            select user_id into old_token_data from admin.refresh_tokens where token = last_refresh_token_p;
            
            if old_token_data is null then
                RAISE EXCEPTION 'Token de atualização inválido!' USING ERRCODE = 'P0401';
            end if;

            -- Gera novo Access Token
            SELECT * INTO new_access_token FROM admin.generate_bearer_token_com_abilities(old_token_data.user_id);
            
            -- Gera novo Refresh Token (consumindo o antigo)
            SELECT * INTO new_refresh_token FROM admin.generate_refresh_token(new_access_token.id, old_token_data.user_id, last_refresh_token_p);

            RETURN QUERY
                SELECT json_build_object(
                               'access_token', json_build_object(
                                    'token', new_access_token.token,
                                    'created_at', new_access_token.created_at
                                ),
                               'refresh_token', json_build_object(
                                    'token', new_refresh_token.token,
                                    'created_at', new_refresh_token.created_at
                               ),
                               'user_data', D.data::JSON
                       )
                FROM admin.get_user_by_token(new_access_token.token, FALSE) D;
        end;
        $$;");

        DB::statement("CREATE OR REPLACE FUNCTION admin.process_logout(token_p text)
            returns void
            language plpgsql
        as
        $$
        begin
            -- Invalida o Access Token
            UPDATE admin.personal_access_tokens 
            SET expires_at = now() 
            WHERE token = token_p;
            
            -- Invalida o Refresh Token associado
            UPDATE admin.refresh_tokens 
            SET consumed_at = now() 
            WHERE personal_access_token_id = (SELECT id FROM admin.personal_access_tokens WHERE token = token_p);
        end;
        $$;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS admin.process_refresh(last_refresh_token_p text)');
        DB::statement('DROP FUNCTION IF EXISTS admin.process_logout(token_p text)');
    }
};
