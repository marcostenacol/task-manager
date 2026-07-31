<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create or replace function admin.check_hash_constant_time(password_hash text, password text) returns boolean
    immutable
    strict
    language plpgsql
as
$$
DECLARE
    diff int := 0;
    i    int;
BEGIN
    IF length(password_hash) <> length(password) THEN
        RETURN false;
    END IF;

    FOR i IN 1..length(password_hash)
        LOOP
            diff := diff | (ascii(substr(password_hash, i, 1)) # ascii(substr(password, i, 1)));
        END LOOP;

    RETURN diff = 0;
END;
$$;');

        DB::statement("create or replace function admin.generate_token_text() returns text
    language plpgsql
as
$$
BEGIN
    RETURN encode(gen_random_bytes(32), 'hex');
END;
$$;");

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
    SELECT array_agg(DISTINCT name) AS data FROM tmp_permissions;
END;
$$;');

        DB::statement("create or replace function admin.generate_bearer_token_com_abilities(user_id_p uuid)
    returns TABLE
            (
                id         uuid,
                token      text,
                created_at timestamp without time zone
            )
    language plpgsql
as
$$
declare
    token_val text;
begin
    token_val := admin.generate_token_text();

    RETURN QUERY
        WITH tmp_permissions as (select unnest(data) as name from admin.get_permission_names_by_user_id(user_id_p)),
             tmp_access_token AS (
                 INSERT INTO admin.personal_access_tokens (id, user_id, token, abilities, expires_at, created_at)
                     values (gen_random_uuid(),
                             user_id_p,
                             token_val,
                             coalesce((select json_agg(name) from tmp_permissions)::text, '[]'::text),
                             now() + interval '1 day',
                             now())
                     RETURNING *)
        SELECT T.id, T.token::TEXT, T.created_at
        FROM tmp_access_token T;
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
            UPDATE admin.refresh_tokens SET consumed_at = now() WHERE user_id = old_refresh_token_v.user_id AND consumed_at IS NULL;
            UPDATE admin.personal_access_tokens SET expires_at = now() WHERE user_id = old_refresh_token_v.user_id AND expires_at > now();
            RAISE EXCEPTION 'Aviso de Segurança: Token de atualização inválido ou reciclado, logue novamente nas suas sessões!' USING ERRCODE = 'P0401';
        end if;

        -- TOKEN EXPIRADO TEMPORALMENTE
        IF old_refresh_token_v.expires_at < now() THEN
            UPDATE admin.refresh_tokens SET consumed_at = now() WHERE id = old_refresh_token_v.id;
            RAISE EXCEPTION 'Acesso expirado, por favor, realize o login novamente!' USING ERRCODE = 'P0401';
        end if;
        
        -- MARCA COMO CONSUMIDO EM VEZ DE DELETAR
        UPDATE admin.refresh_tokens SET consumed_at = now() WHERE id = old_refresh_token_v.id;
        
        -- DERRUBA O BEARER ATUAL (Caso o hacker tente usar o Access Token que estava com o pai)
        UPDATE admin.personal_access_tokens SET expires_at = now() WHERE id = old_refresh_token_v.personal_access_token_id;
    END IF;

    new_refresh_token_v := admin.generate_token_text();

    RETURN QUERY
        WITH tmp_refresh_token AS (
            INSERT INTO admin.refresh_tokens (token, user_id, personal_access_token_id, expires_at, created_at)
                VALUES (new_refresh_token_v::TEXT,
                        user_id_p,
                        personal_access_token_id_p,
                        now() + interval '30 days',
                        now())
                RETURNING *)
        SELECT RT.id,
               RT.token::TEXT,
               RT.created_at
        FROM tmp_refresh_token RT;

end;
$$;");

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
            SELECT PT.user_id, PT.id 
            FROM admin.personal_access_tokens PT 
            WHERE PT.token = token_p
        ),
        tmp_refresh_token AS (
            SELECT RT.token 
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
               'access_token', token_p,
               'refresh_token', coalesce((select token from tmp_refresh_token), null),
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
        LEFT JOIN user_role UR ON TRUE
        LEFT JOIN tmp_user_status US ON TRUE;
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
                            'created_at', access_token.created_at
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

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS admin.process_login(email_p text, password_p text)');
        DB::statement('DROP FUNCTION IF EXISTS admin.get_user_by_token(token_p text, dispatch_error boolean)');
        DB::statement('DROP FUNCTION IF EXISTS admin.get_permission_names_by_user_id(user_id_p uuid)');
        DB::statement('DROP FUNCTION IF EXISTS admin.generate_bearer_token_com_abilities(user_id_p uuid)');
        DB::statement('DROP FUNCTION IF EXISTS admin.generate_refresh_token(personal_access_token_id_p uuid, user_id_p uuid, last_refresh_token_p text)');
        DB::statement('DROP FUNCTION IF EXISTS admin.generate_token_text()');
        DB::statement('DROP FUNCTION IF EXISTS admin.check_hash_constant_time(password_hash text, password text)');
    }
};
