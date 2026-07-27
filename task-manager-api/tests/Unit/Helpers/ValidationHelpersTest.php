<?php

test('isValidCpf deve validar um CPF numericamente correto', function () {
    expect(isValidCpf('111.444.777-35'))->toBeTrue();
});

test('isValidCpf deve rejeitar um CPF com dígitos verificadores incorretos', function () {
    expect(isValidCpf('111.444.777-36'))->toBeFalse();
});

test('isValidCpf deve rejeitar sequências repetidas de dígitos', function () {
    expect(isValidCpf('111.111.111-11'))->toBeFalse();
});

test('isValidCpf deve rejeitar valor vazio ou nulo', function () {
    expect(isValidCpf(''))->toBeFalse();
    expect(isValidCpf(null))->toBeFalse();
});

test('isValidCodeFamily deve aceitar código com 11 dígitos', function () {
    expect(isValidCodeFamily('12345678901'))->toBeTrue();
});

test('isValidCodeFamily deve aceitar código no formato 9 dígitos + hífen + 2 dígitos', function () {
    expect(isValidCodeFamily('123456789-01'))->toBeTrue();
});

test('isValidCodeFamily deve rejeitar formato inválido', function () {
    expect(isValidCodeFamily('abc123'))->toBeFalse();
    expect(isValidCodeFamily(''))->toBeFalse();
});

test('handlerRequestToken deve extrair a segunda parte de um token com separador |', function () {
    expect(handlerRequestToken('1|abc123def456'))->toBe('abc123def456');
});

test('handlerRequestToken deve retornar o próprio token quando não há separador', function () {
    expect(handlerRequestToken('abc123def456'))->toBe('abc123def456');
});
