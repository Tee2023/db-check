<?php

namespace Database\Schema;

class UserSchema
{
    public static function definition(): array
    {
        return [
            'id' => [
                'type' => 'bigInteger',
                'unsigned' => true,
                'autoIncrement' => true,
                'primary' => true,
                'nullable' => false,
            ],

            'name' => [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
            ],

            'email' => [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
                'unique' => true,
            ],

            'phone_number' => [
                'type' => 'string',
                'length' => 255,
                'nullable' => true,
            ],

            'birth_date' => [
                'type' => 'date',
                'nullable' => true,
            ],

            'is_active' => [
                'type' => 'boolean',
                'nullable' => false,
                'default' => true,
            ],

            'email_verified_at' => [
                'type' => 'timestamp',
                'nullable' => true,
            ],

            'password' => [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
            ],

            'remember_token' => [
                'type' => 'string',
                'length' => 100,
                'nullable' => true,
            ],

            'created_at' => [
                'type' => 'timestamp',
                'nullable' => true,
            ],

            'updated_at' => [
                'type' => 'timestamp',
                'nullable' => true,
            ],
        ];
    }
}