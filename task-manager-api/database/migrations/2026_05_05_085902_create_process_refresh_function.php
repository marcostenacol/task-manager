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
            select * into old_token_data from admin.refresh_tokens where token = last_refresh_token_p;
            
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
                                    'created_at', new_access_token.created_at,
                                    'user_id', old_token_data.user_id
                                ),
                               'refresh_token', json_build_object(
                                    'token', new_refresh_token.token,
                                    'created_at', new_refresh_token.created_at
                               ),
                               'user_data', D.data::JSON,
                               'old_access_token', (SELECT token FROM admin.personal_access_tokens WHERE id = old_token_data.personal_access_token_id)
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
            SET expires_at = now() - interval '1 minute' 
            WHERE token = token_p;
            
            -- Invalida o Refresh Token associado
            UPDATE admin.refresh_tokens 
            SET consumed_at = now() 
            WHERE personal_access_token_id = (SELECT id FROM admin.personal_access_tokens WHERE token = token_p);
        end;
        $$;");

        DB::statement("create or replace function admin.generate_refresh_token(personal_access_token_id_p uuid, user_id_p uuid, last_refresh_token_p text DEFAULT NULL::text)
    returns TABLE
            (
                id         bigint,
                token      text,
                created_at timestamp without time zone
            )
    language plpgsql
as
$$
declare
    old_refresh_token_v RECORD;
    new_refresh_token_v text;
BEGIN

    IF (last_refresh_token_p::TEXT IS NOT NULL)::BOOLEAN THEN

        IF (select count(*) = 0
            from admin.refresh_tokens RT
            where RT.token = last_refresh_token_p) THEN

            RAISE EXCEPTION 'Token de atualização não encontrado, por favor realize o login!' USING ERRCODE = 'P0401';
        end if;

        SELECT RT.*
        INTO old_refresh_token_v
        FROM admin.refresh_tokens RT
        WHERE RT.token = last_refresh_token_p
        LIMIT 1;

        -- TOKEN ROUBADO (DETECÇÃO DE REUSO)
        IF old_refresh_token_v.consumed_at IS NOT NULL THEN
            UPDATE admin.refresh_tokens RT SET consumed_at = now() WHERE RT.user_id = old_refresh_token_v.user_id AND RT.consumed_at IS NULL;
            UPDATE admin.personal_access_tokens PT SET expires_at = now() - interval '1 minute' WHERE PT.user_id = old_refresh_token_v.user_id AND PT.expires_at > now();
            RAISE EXCEPTION 'Aviso de Segurança: Token de atualização inválido ou reciclado, logue novamente nas suas sessões!' USING ERRCODE = 'P0401';
        end if;

        -- TOKEN EXPIRADO TEMPORALMENTE
        IF old_refresh_token_v.expires_at < now() THEN
            UPDATE admin.refresh_tokens RT SET consumed_at = now() WHERE RT.id = old_refresh_token_v.id;
            RAISE EXCEPTION 'Acesso expirado, por favor, realize o login novamente!' USING ERRCODE = 'P0401';
        end if;
        
        -- MARCA COMO CONSUMIDO EM VEZ DE DELETAR
        UPDATE admin.refresh_tokens RT SET consumed_at = now() WHERE RT.id = old_refresh_token_v.id;
        
        -- DERRUBA O BEARER ATUAL (Caso o hacker tente usar o Access Token que estava com o pai)
        UPDATE admin.personal_access_tokens PT SET expires_at = now() - interval '1 minute' WHERE PT.id = old_refresh_token_v.personal_access_token_id;
    END IF;

    new_refresh_token_v := admin.generate_token_text();

    RETURN QUERY
        WITH tmp_refresh_token AS (
            INSERT INTO admin.refresh_tokens (personal_access_token_id, user_id, token, expires_at, created_at)
                values (personal_access_token_id_p,
                        user_id_p,
                        new_refresh_token_v,
                        now() + interval '30 days',
                        now())
                RETURNING *)
        SELECT RT.id,
               RT.token::TEXT,
               RT.created_at
        FROM tmp_refresh_token RT;

end;
$$;");

        DB::statement("create or replace function admin.process_login(email_p text, password_p text)
            returns TABLE
                    (
                        data json
                    )
            language plpgsql
        as
        $$
        declare
            user_data         record;
            access_token      RECORD;
            refresh_token     RECORD;
        begin
        
            select u.id,
                   u.password,
                   s.slug as status_slug
            into user_data
            from admin.users u
            left join admin.user_statuses s on u.last_status_id = s.id
            where u.email = email_p;
        
            IF NOT FOUND THEN
                RAISE EXCEPTION 'Usuário não encontrado!' USING ERRCODE = 'P0400';
            END IF;
        
            IF user_data.status_slug != 'active' AND user_data.status_slug IS NOT NULL THEN
                RAISE EXCEPTION 'Usuário inativo ou banido!' USING ERRCODE = 'P0401';
            end if;
        
            IF (SELECT admin.check_hash_constant_time(crypt(password_p, user_data.password::text)::text,
                                                      user_data.password::text))::BOOLEAN != TRUE THEN
                RAISE EXCEPTION 'Usuário ou senha incorretos!' USING ERRCODE = 'P0400';
            END IF;
        
            SELECT *
            INTO access_token
            FROM admin.generate_bearer_token_com_abilities(user_data.id);
            
            SELECT *
            INTO refresh_token
            FROM admin.generate_refresh_token(access_token.id, user_data.id);
        
            RETURN QUERY
                SELECT json_build_object(
                               'access_token', json_build_object(
                                    'token', access_token.token,
                                    'created_at', access_token.created_at,
                                    'user_id', user_data.id
                                ),
                               'refresh_token', json_build_object(
                                    'token', refresh_token.token,
                                    'created_at', refresh_token.created_at
                               ),
                               'user_data', D.data::JSON
                       )
                FROM admin.get_user_by_token(access_token.token, FALSE) D;
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
