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
     *
     * @param string $username
     * @param string $password
     * @return object
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
     *
     * @param string $refreshToken
     * @return object
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
     *
     * @param string $token
     * @return void
     */
    public function logout(string $token): void
    {
        DB::statement(
            'select admin.process_logout(?);',
            [$token]
        );
    }
}
