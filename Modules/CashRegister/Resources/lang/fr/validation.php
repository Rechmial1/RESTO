<?php

return [
    // Messages de validation pour la dénomination
    'denomination' => [
        'name' => [
            'required' => 'Le nom de la dénomination est requis.',
            'max' => 'Le nom de la dénomination ne peut pas dépasser :max caractères.',
        ],
        'value' => [
            'required' => 'La valeur de la dénomination est requise.',
            'numeric' => 'La valeur de la dénomination doit être un nombre.',
            'min' => 'La valeur de la dénomination doit être d\'au moins :min.',
            'max' => 'La valeur de la dénomination ne peut pas dépasser :max.',
        ],
        'type' => [
            'required' => 'Le type de dénomination est requis.',
            'in' => 'Le type de dénomination sélectionné est invalide.',
        ],
        // currency removed
        'description' => [
            'max' => 'La description de la dénomination ne peut pas dépasser :max caractères.',
        ],
    ],
];