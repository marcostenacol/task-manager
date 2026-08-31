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
        DB::statement('DROP FUNCTION IF EXISTS admin.process_login(text, text)');
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
    select *
    into user_data
    from admin.users
    where email = email_p;

    IF user_data.id IS NULL THEN
        return query select json_build_object('success', false, 'message', 'Usuário ou senha incorretos!');
        return;
    END IF;

    -- Validação usando crypt nativo (o salt é extraído automaticamente do hash armazenado)
    IF user_data.password <> crypt(password_p, user_data.password) THEN
        return query select json_build_object('success', false, 'message', 'Usuário ou senha incorretos!');
        return;
    END IF;

    IF user_data.last_status_id = (select id from admin.user_statuses where slug = 'banned') THEN
        return query select json_build_object('success', false, 'message', 'Sua conta foi banida. Entre em contato com o suporte.');
        return;
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
        DB::statement('DROP FUNCTION IF EXISTS admin.process_login(text, text)');
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
    select *
    into user_data
    from admin.users
    where email = email_p;

    IF user_data.id IS NULL THEN
        return query select json_build_object('success', false, 'message', 'Usuário não encontrado!');
        return;
    END IF;

    -- Validação usando crypt nativo (o salt é extraído automaticamente do hash armazenado)
    IF user_data.password <> crypt(password_p, user_data.password) THEN
        return query select json_build_object('success', false, 'message', 'Usuário ou senha incorretos!');
        return;
    END IF;

    IF user_data.last_status_id = (select id from admin.user_statuses where slug = 'banned') THEN
        return query select json_build_object('success', false, 'message', 'Sua conta foi banida. Entre em contato com o suporte.');
        return;
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
};
