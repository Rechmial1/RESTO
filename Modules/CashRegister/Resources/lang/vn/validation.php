<?php

return [
    // Thông báo xác thực Mệnh giá
    'denomination' => [
        'name' => [
            'required' => 'Tên mệnh giá là bắt buộc.',
            'max' => 'Tên mệnh giá không được vượt quá :max ký tự.',
        ],
        'value' => [
            'required' => 'Giá trị mệnh giá là bắt buộc.',
            'numeric' => 'Giá trị mệnh giá phải là một số.',
            'min' => 'Giá trị mệnh giá phải ít nhất là :min.',
            'max' => 'Giá trị mệnh giá không được lớn hơn :max.',
        ],
        'type' => [
            'required' => 'Loại mệnh giá là bắt buộc.',
            'in' => 'Loại mệnh giá được chọn không hợp lệ.',
        ],
        // currency removed
        'description' => [
            'max' => 'Mô tả mệnh giá không được vượt quá :max ký tự.',
        ],
    ],
];
