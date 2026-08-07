<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

it('translates every framework validation rule into Italian', function () {
    $vendorMessages = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
    $italianMessages = require lang_path('it/validation.php');

    $ruleKeys = collect(Arr::dot($vendorMessages))
        ->keys()
        ->reject(fn (string $key): bool => str_starts_with($key, 'custom.') || str_starts_with($key, 'attributes'));

    $missingKeys = $ruleKeys->reject(fn (string $key): bool => Arr::has($italianMessages, $key));

    expect($missingKeys->values()->all())->toBeEmpty();
});

it('resolves validation messages in Italian', function () {
    app()->setLocale('it');

    $errors = Validator::make(
        ['serial_number' => null, 'quantity' => 'abc'],
        ['serial_number' => 'required', 'quantity' => 'integer'],
    )->errors();

    expect($errors->first('serial_number'))->toBe('Il campo serial number è obbligatorio.')
        ->and($errors->first('quantity'))->toBe('Il campo quantity deve essere un numero intero.');
});
