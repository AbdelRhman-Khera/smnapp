<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'email' => 'The :attribute must be a valid email address.',
    'unique' => 'The :attribute has already been taken.',
    'same' => 'The :attribute and :other must match.',
    'regex' => 'The :attribute format is invalid.',
    'confirmed' => 'The :attribute confirmation does not match.',


    'phone' => [
        'required' => 'The phone number is required.',
        'unique' => 'This phone number is already taken.',
        'regex' => 'The phone number format is invalid. Please enter a valid Saudi phone number.',
    ],
    'first_name' => 'The first name is required and should be a string with a maximum length of 100 characters.',
    'last_name' => 'The last name should be a string with a maximum length of 100 characters.',
    'password' => 'The password is required and should be at least 8 characters.',
    'confirm_password' => 'The confirm password field is required and must match the password.',


    'attributes' => [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'phone' => 'Phone Number',
        'email' => 'Email Address',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
    ],
];
