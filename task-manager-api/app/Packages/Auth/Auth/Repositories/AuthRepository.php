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
}
