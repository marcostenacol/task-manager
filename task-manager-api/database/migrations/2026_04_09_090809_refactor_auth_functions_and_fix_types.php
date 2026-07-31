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
        // 1. Geração de Hash Centralizada no Banco
        DB::statement("CREATE OR REPLACE FUNCTION admin.generate_password_hash(password text) returns text
                        language plpgsql
                    as
                    $$
                    begin
                        return crypt(password, gen_salt('bf'))::text;
                    end;
                    $$;");

        // 2. Correção de Datatype Mismatch (varying[] -> text[])
        DB::statement('create or replace function admin.get_permission_names_by_user_id(user_id_p uuid)
            returns TABLE(data text[])
            language plpgsql
        as
        $$
        BEGIN
            RETURN QUERY
            WITH tmp_permissions AS (
                SELECT P.name
                FROM admin.users U
                JOIN admin.roles R ON U.role_id = R.id
                JOIN admin.role_has_permissions RP ON RP.role_id = R.id
                JOIN admin.permissions P ON RP.permission_id = P.id
                WHERE U.id = user_id_p
            )
            SELECT array_agg(DISTINCT name)::text[] AS data FROM tmp_permissions;
        END;
        $$;');

        // 3. Atualização para Retorno de Objetos de Token Detalhandos
        DB::statement("CREATE OR REPLACE FUNCTION admin.get_user_by_token(token_p text, dispatch_error boolean DEFAULT true)
            returns TABLE(data json)
            language plpgsql
        as
        $$
        begin
            IF ((select id from admin.personal_access_tokens pt WHERE pt.token = token_p) is null) THEN
                IF (dispatch_error = TRUE) THEN
                    RAISE EXCEPTION 'Identificação de acesso não encontrada, por favor realize o seu login!';
                end if;

                RETURN QUERY select null::JSON AS data;
                RETURN;
            end if;

            IF (select expires_at < now() from admin.personal_access_tokens pt WHERE pt.token = token_p) THEN
                IF (dispatch_error = TRUE) THEN
                    RAISE EXCEPTION 'Token expirado, por favor realize o seu login novamente!';
                end if;
                RETURN QUERY select null::JSON AS data;
                RETURN;
            end if;

            RETURN QUERY
                WITH tmp_personal_access_token AS (
                    SELECT PT.user_id, PT.id, PT.token, PT.created_at 
                    FROM admin.personal_access_tokens PT 
                    WHERE PT.token = token_p
                ),
                tmp_refresh_token AS (
                    SELECT RT.token, RT.created_at 
                    FROM admin.refresh_tokens RT 
                    JOIN tmp_personal_access_token PAT ON PAT.id = RT.personal_access_token_id
                    WHERE RT.consumed_at IS NULL
                    LIMIT 1
                ),
                tmp_user AS (
                    SELECT U.* 
                    FROM admin.users U 
                    JOIN tmp_personal_access_token PAT ON PAT.user_id = U.id
                ),
                tmp_user_status AS (
                    SELECT S.slug, S.name 
                    FROM admin.user_statuses S 
                    JOIN tmp_user TU ON TU.last_status_id = S.id
                    LIMIT 1
                ),
                user_role AS (
                    SELECT R.slug, R.name 
                    FROM admin.roles R 
                    JOIN tmp_user U ON U.role_id = R.id
                    LIMIT 1
                )
                select json_build_object(
                       'access_token', json_build_object(
                            'token', PAT.token,
                            'created_at', PAT.created_at
                       ),
                       'refresh_token', coalesce((
                            select json_build_object(
                                'token', token,
                                'created_at', created_at
                            ) from tmp_refresh_token
                       ), null),
                       'user', json_build_object(
                               'id', U.id,
                               'name', U.name,
                               'email', U.email,
                               'status', json_build_object(
                                       'slug', US.slug,
                                       'name', US.name
                               ),
                               'role', json_build_object(
                                       'name', UR.name,
                                       'slug', UR.slug
                               ),
                               'permissions', coalesce((SELECT X.data FROM admin.get_permission_names_by_user_id(U.id) X) , '{}')
                       )
                )::JSON AS data
                from tmp_user U
                CROSS JOIN tmp_personal_access_token PAT
                LEFT JOIN user_role UR ON TRUE
                LEFT JOIN tmp_user_status US ON TRUE;
        end;
        $$;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nota: O down não reverte o código das funções para o estado anterior,
        // apenas remove a função de geração de hash que é nova.
        DB::statement('DROP FUNCTION IF EXISTS admin.generate_password_hash(password text)');
    }
};
