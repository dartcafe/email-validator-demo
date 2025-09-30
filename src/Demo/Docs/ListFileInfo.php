<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Docs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ListFileInfo',
    type: 'object',
    required: ['path', 'content'],
    properties: [
        new OA\Property(property: 'path', type: 'string', example: 'blacklists/banned_addresses.txt'),
        new OA\Property(property: 'content', type: 'string', example: "ceo@example.com\nvip@example.com"),
        // Die Demo liefert derzeit auch fullPath – optional dokumentieren:
        new OA\Property(property: 'fullPath', type: 'string', nullable: true, example: '/var/www/.../config/blacklists/banned_addresses.txt'),
    ],
)]
final class ListFileInfo
{
}

#[OA\Schema(
    schema: 'ListsGetResponse',
    type: 'object',
    required: ['iniPath', 'ini', 'files'],
    properties: [
        new OA\Property(property: 'iniPath', type: 'string', example: 'config/lists.ini'),
        new OA\Property(property: 'ini', type: 'string', example: "[deny_banned_addresses]\n type=deny\n checkType=address\n listFileName=blacklists/banned_addresses.txt\n"),
        new OA\Property(
            property: 'files',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(ref: '#/components/schemas/ListFileInfo'),
            example: [
                'deny_banned_addresses' => [
                    'path'     => 'blacklists/banned_addresses.txt',
                    'fullPath' => '/var/www/.../config/blacklists/banned_addresses.txt',
                    'content'  => "ceo@example.com\nceo2@example.com\n",
                ],
            ],
        ),
    ],
)]
final class ListsGetResponse
{
}

#[OA\Schema(
    schema: 'ListsSaveRequest',
    type: 'object',
    required: ['ini', 'files'],
    properties: [
        new OA\Property(property: 'ini', type: 'string'),
        new OA\Property(
            property: 'files',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'object',
                required: ['path','content'],
                properties: [
                    new OA\Property(property: 'path', type: 'string', example: 'blacklists/banned_addresses.txt'),
                    new OA\Property(property: 'content', type: 'string', example: "ceo@example.com\n"),
                ],
            ),
            example: [
                'deny_banned_addresses' => [
                    'path'    => 'blacklists/banned_addresses.txt',
                    'content' => "ceo@example.com\n",
                ],
            ],
        ),
    ],
    example: [
        'ini'   => "[deny_banned_addresses]\n type=deny\n checkType=address\n listFileName=blacklists/banned_addresses.txt\n",
        'files' => [
            'deny_banned_addresses' => [
                'path'    => 'blacklists/banned_addresses.txt',
                'content' => "ceo@example.com\n",
            ],
        ],
    ],
)]
final class ListsSaveRequest
{
}


#[OA\Schema(
    schema: 'OkResponse',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'ok'),
    ],
)]
final class OkResponse
{
}

#[OA\Schema(
    schema: 'ImportSummary',
    type: 'object',
    required: ['ini','files'],
    properties: [
        new OA\Property(property: 'ini', type: 'boolean', example: true),
        new OA\Property(property: 'files', type: 'integer', example: 3),
    ],
)]
final class ImportSummary
{
}

#[OA\Schema(
    schema: 'ImportResponse',
    type: 'object',
    required: ['status','summary'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'ok'),
        new OA\Property(property: 'summary', ref: '#/components/schemas/ImportSummary'),
    ],
)]
final class ImportResponse
{
}
