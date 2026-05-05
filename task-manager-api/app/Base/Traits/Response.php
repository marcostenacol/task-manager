<?php

namespace App\Base\Traits;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response AS HttpResponse;

trait Response {

    use HandlerLog;

    /**
     * @param mixed $data
     * @param string|null $message
     * @param int|null $status_code
     * @param bool $return_data_null
     * @return JsonResponse
     */
    public static function successResponse(
        mixed       $data = null,
        string|null $message = 'Operação realizada com sucesso.',
        int|null    $status_code = 200,
        bool $return_data_null = false
    ): JsonResponse {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: true, message: $message),
            data_error: false,
            data: $data,
            return_data_null: $return_data_null
        );

        return response()->json($response, $status_code);
    }

    /**
     * @param mixed|null $data
     * @param string|null $message
     * @param int|null $status_code
     * @return JsonResponse
     */
    public static function successResponseJson(
        mixed       $data = null,
        string|null $message = 'Operação realizada com sucesso.',
        int|null    $status_code = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status_code);
    }

    /**
     * @param Exception|Throwable $exception
     * @param string|null $message
     * @return JsonResponse
     */
    public static function internalServerErrorResponse(
        Exception|Throwable   $exception,
        string|null $message = 'Não foi possível processar a requisição. Tente novamente mais tarde.',
    ): JsonResponse {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: false, message: $message),
            data_error: true,
            data: $exception->getMessage()
        );

        return response()->json($response, 500);
    }

    /**
     * @param mixed $exception
     * @return HttpResponse|JsonResponse
     */
    public static function returnError(
        mixed       $exception,
    ): HttpResponse|JsonResponse {
        if ($exception instanceof HttpResponseException) {
            return self::httpResponseException($exception);
        }

        if ($exception instanceof QueryException) {
            return self::queryExceptionResponse($exception, $exception->getMessage());
        }

        if ($exception instanceof ModelNotFoundException) {
            return self::modelNotFoundResponse($exception, $exception->getMessage());
        }

        if ($exception instanceof InvalidArgumentException) {
            return self::invalidArgumentResponse($exception, $exception->getMessage());
        }

        if ($exception instanceof ConnectionException) {
            return self::connectionExceptionResponse($exception);
        }

        if ($exception instanceof ConflictHttpException) {
            return self::conflictResponse($exception);
        }

        if ($exception instanceof ValidationException) {
            return self::failedValidationResponse($exception);
        }

        return self::internalServerErrorResponse($exception);
    }

    /**
     * @param ConnectionException $connectionException
     * @return JsonResponse
     */
    public static function connectionExceptionResponse(ConnectionException $connectionException): JsonResponse {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: false, message: 'Não foi possível realizar a conexão com um dos nossos parceiros, por favor tente novamente mais tarde.'),
            data_error: true,
            data: $connectionException->getMessage()
        );

        return response()->json($response, 400);
    }

    /**
     * @param QueryException $queryException
     * @param string|null $message
     * @return JsonResponse
     */
    public static function queryExceptionResponse(
        QueryException $queryException,
        string|null    $message = 'Não foi possível processar a requisição. Tente novamente mais tarde.',
    ): JsonResponse {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: false, message: self::defineQueryMessage($queryException->getMessage())),
            data_error: true,
            data: self::defineQueryMessage($queryException->getMessage())
        );

        $status_code = $queryException->getCode() != 'P0001' ?
            substr($queryException->getCode(), -3) : null;

        if (!in_array($status_code, array_keys(HttpResponse::$statusTexts))) {
            return self::internalServerErrorResponse($queryException);
        }

        return response()->json($response, $status_code ?? 400);
    }

    /**
     * @param string|null $message
     * @return string
     */
    public static function defineQueryMessage(string|null $message): string {
        $default_message = 'Não foi possível processar a requisição. Tente novamente mais tarde.';

        if (!$message) {
            return $default_message;
        }

        if (preg_match('/(?:ERROR|ERRO):\s+(.*?)\s+CONTEXT:/s', $message, $matches)) {
            $custom_message = $matches[1];
        } else {
            $custom_message = $default_message;
        }

        return $custom_message;
    }

    /**
     * @param ModelNotFoundException $modelNotFoundException
     * @param string|null $message
     * @return JsonResponse
     */
    public static function modelNotFoundResponse(
        ModelNotFoundException $modelNotFoundException,
        string|null            $message = null
    ): JsonResponse {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: false, message: $message ?? $modelNotFoundException->getMessage()),
            data_error: true,
            data: $modelNotFoundException->getMessage()
        );

        return response()->json($response, 404);
    }

    /**
     * @param InvalidArgumentException $invalidArgumentException
     * @param string|null $message
     * @return JsonResponse
     */
    public static function invalidArgumentResponse(
        InvalidArgumentException $invalidArgumentException,
        string|null              $message = null,
    ): JsonResponse {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: false, message: $message ?? $invalidArgumentException->getMessage()),
            data_error: true,
            data: $invalidArgumentException->getMessage()
        );

        return response()->json($response, 400);
    }

    /**
     * @param HttpResponseException $httpResponseException
     * @return HttpResponse
     */
    public static function httpResponseException(HttpResponseException $httpResponseException): HttpResponse {
        return $httpResponseException->getResponse();
    }

    /**
     * @param $validator
     * @return mixed
     */
    public static function failedValidationResponse($validator): mixed {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: false, message: 'Por favor verifique os campos preenchidos.'),
            data_error: true,
            data: $validator->errors(),
            error_key: 'errors',
            validator_error: true
        );

        throw new HttpResponseException(
            response()->json($response, 422)
        );
    }

    /**
     * @param array $response
     * @param bool $data_error
     * @param mixed $data
     * @param string|null $error_key
     * @param bool $validator_error
     * @param bool $return_data_null
     * @return array
     */
    private static function defineResponseData(
        array   $response,
        bool    $data_error,
        mixed   $data,
        ?string $error_key = 'message_error',
        bool    $validator_error = false,
        bool    $return_data_null = false
    ): array {
        if ($data_error && !config('logging.show_error_message') && !$validator_error) {
            return $response;
        }

        if ($data_error) {
            $response['data'] = [
                $error_key => $data
            ];

            return $response;
        }

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        if (is_null($data)  && $return_data_null) {
            $response['data'] = null;
        }

        return $response;
    }

    /**
     * @param bool $success
     * @param $message
     * @return array
     */
    private static function defineResponseBase(bool $success, $message): array {
        return [
            'success' => $success,
            'message' => $message
        ];
    }

    public static function notAuthorizeExceptionResponse(
        string $message = 'Não autorizado.',
        int $status_code = 401,
    ) {
        return throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => ['errors' => $message],
            'message' => $message
        ], $status_code));
    }

    /**
     * @param bool $success
     * @param string $message
     * @param array $errors
     * @param int $status_code
     * @return JsonResponse
     */
    public static function customValidationResponse(
        bool   $success = false,
        string $message = 'Por favor verifique os campos preenchidos.',
        array  $errors = [],
        int    $status_code = 422
    ): JsonResponse {
        $response = self::defineResponseData(
            response: self::defineResponseBase(success: $success, message: $message),
            data_error: true,
            data: $errors,
            error_key: 'errors',
            validator_error: true
        );

        throw new HttpResponseException(
            response()->json($response, $status_code)
        );
    }

    /**
     * @param ConflictHttpException $conflictHttpException
     * @param string|null $message
     * @return JsonResponse
     */
    public static function conflictResponse(
        ConflictHttpException $conflictHttpException,
        string|null $message = null
    ): JsonResponse {
        $finalMessage = $message ?? $conflictHttpException->getMessage() ?? 'Conflito de estado do recurso.';

        $response = self::defineResponseData(
            response: self::defineResponseBase(
                success: false,
                message: $finalMessage
            ),
            data_error: true,
            data: $finalMessage
        );

        return response()->json($response, HttpResponse::HTTP_CONFLICT);
    }

}
