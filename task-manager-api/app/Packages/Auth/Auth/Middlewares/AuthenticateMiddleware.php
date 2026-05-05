<?php

namespace App\Packages\Auth\Auth\Middlewares;

use App\Base\Traits\CacheTrait;
use App\Base\Traits\Response;
use App\Packages\Auth\Auth\Enum\SettingsEnum;
use App\Packages\Auth\Auth\Services\Cache\TokenInCacheService;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthenticateMiddleware {
    use Response, CacheTrait;

    public function handle(Request $request, Closure $next, ...$abilities): mixed {
        try {
            $token = handlerRequestToken($request->bearerToken());
            
            $this->checkIfTokenIsValid(
                data: app(TokenInCacheService::class)->execute($token)
            );

            if (!$request->routeIs('v1.auth.terms-acceptance', 'v1.auth.logout', 'v1.auth.me')) {
                $userData = userObject();
                // Verificação de termos de uso (se existir no seu projeto)
                if ($userData && isset($userData->terms_accepted) && !$userData->terms_accepted) {
                    return self::notAuthorizeExceptionResponse(
                        message: 'É necessário aceitar os termos de uso atualizados para continuar.',
                        status_code: 403
                    );
                }
            }

            if ($abilities) {
                $permissions = data_get(entityObject(), 'user.permissions', []);
                if (!(count(array_intersect($abilities, $permissions)) == count($abilities))) {
                    return self::notAuthorizeExceptionResponse(
                        message: 'Você não possui permissão para acessar esse recurso!',
                        status_code: 403
                    );
                }
            }

            return $next($request);
        } catch (Exception $exception) {
            return self::returnError($exception);
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    private function checkIfTokenIsValid($data = null): mixed {
        if (!$data) {
            return self::notAuthorizeExceptionResponse(
                message: 'Você precisa estar conectado para acessar esse recurso, por favor realize o login!'
            );
        }

        $expirationMinutes = (int)SettingsEnum::getValue(SettingsEnum::TOKEN_EXPIRATION_MINUTES);
        
        if (isset($data->is_expired) && $data->is_expired) {
            $this->revokeToken(personal_access_token_id: $data->id, access_token: $data->token);
            return self::notAuthorizeExceptionResponse('Acesso expirado, por favor, realize o login novamente!');
        }

        if (Carbon::parse($data->created_at)->addMinutes($expirationMinutes) < now()) {
            $this->revokeToken(personal_access_token_id: $data->id, access_token: $data->token);
            return self::notAuthorizeExceptionResponse('Acesso expirado, por favor, realize o login novamente!');
        }

        if (isset($data->is_revoked) && $data->is_revoked) {
            return self::notAuthorizeExceptionResponse('Acesso expirado, por favor, realize o login novamente!');
        }

        return $data;
    }

    private function revokeToken($personal_access_token_id, $access_token): void {
        DB::statement("
            UPDATE admin.personal_access_tokens PAT SET expires_at = now() WHERE PAT.id = ?;
        ", [$personal_access_token_id]
        );

        $this->clearCache('token_', $access_token);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function failedAuthentication(): mixed {
        return self::notAuthorizeExceptionResponse();
    }
}
