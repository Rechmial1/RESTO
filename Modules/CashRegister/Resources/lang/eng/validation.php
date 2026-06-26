<?php

return [
    // Denomination validation messages
    'denomination' => [
        'name' => [
            'required' => 'The denomination name is required.',
            'max' => 'The denomination name may not be greater than :max characters.',
        ],
        'value' => [
            'required' => 'The denomination value is required.',
            'numeric' => 'The denomination value must be a number.',
            'min' => 'The denomination value must be at least :min.',
            'max' => 'The denomination value may not be greater than :max.',
        ],
        'type' => [
            'required' => 'The denomination type is required.',
            'in' => 'The selected denomination type is invalid.',
        ],
        // currency removed
        'description' => [
            'max' => 'The denomination description may not be greater than :max characters.',
        ],
    ],
];
