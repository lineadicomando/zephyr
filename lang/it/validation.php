<?php

return [

    'unique' => 'Il valore del campo ":attribute" è già presente.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */
    'required_without' => 'Il campo :attribute è richiesto se :values non è presente',
    'custom' => [
        'model_class' => [
            'unique' => 'custom-message',
        ],
    ],

];
