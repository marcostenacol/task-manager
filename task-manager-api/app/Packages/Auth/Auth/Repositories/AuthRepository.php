<?php

/**
 * [REEST] Repository para autenticação via funções do Postgres.
 */

namespace App\Packages\Auth\Auth\Repositories;

use Illuminate\Support\Facades\DB;

class AuthRepository
{
    /**
     * Processa o login através da função admin.process_login.
     */
    public function processLogin(string $username, string $password): object
    {
        $result = DB::selectOne(
            'select * from admin.process_login(?, ?);',
            [$username, $password]
        );

        return json_decode($result->data);
    }

    /**
     * Processa o refresh do token.
     */
    public function processRefresh(string $refreshToken): object
    {
        $result = DB::selectOne(
            'select * from admin.process_refresh(?);',
            [$refreshToken]
        );

        return json_decode($result->data);
    }

    /**
     * Realiza o logout invalidando os tokens.
     */
    public function logout(string $token): void
    {
        DB::statement(
            'select admin.process_logout(?);',
            [$token]
        );
    }
}
