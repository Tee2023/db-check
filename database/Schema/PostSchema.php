<?php

namespace Database\Schema;

class PostSchema
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

            'user_id' => [
                'type' => 'bigInteger',
                'unsigned' => true,
                'nullable' => false,
            ],

            'title' => [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
            ],

            'content' => [
                'type' => 'text',
                'nullable' => true,
            ],

            'status' => [
                'type' => 'enum',
                'values' => [
                    'draft',
                    'published',
                    'archived',
                ],
                'nullable' => false,
                'default' => 'draft',
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