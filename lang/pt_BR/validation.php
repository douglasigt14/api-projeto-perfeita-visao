<?php

// Só as regras usadas pela API; o resto cai no fallback em inglês.
return [
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'date' => 'O campo :attribute não é uma data válida.',
    'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'exists' => 'O :attribute selecionado é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',

    'attributes' => [
        'name' => 'nome',
        'birth_date' => 'data de nascimento',
        'phone_number' => 'telefone',
        'city_id' => 'cidade',
        'pix_key' => 'chave PIX',
        'instagram_handle' => 'Instagram',
        'password' => 'senha',
        'device_name' => 'nome do dispositivo',
    ],
];
