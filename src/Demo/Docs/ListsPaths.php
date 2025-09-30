<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Docs;

use OpenApi\Attributes as OA;

final class ListsPaths
{
    #[OA\Get(
        path: '/lists',
        operationId: 'listsGet',
        tags: ['lists'],
        summary: 'Get lists.ini and referenced files',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current lists config and files',
                content: new OA\JsonContent(ref: '#/components/schemas/ListsGetResponse'),
            ),
        ],
    )]
    public function get(): void
    {
    }

    #[OA\Post(
        path: '/lists',
        operationId: 'listsSave',
        tags: ['lists'],
        summary: 'Save lists.ini and referenced files',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ListsSaveRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saved',
                content: new OA\JsonContent(ref: '#/components/schemas/OkResponse'),
            ),
            new OA\Response(
                response: 415,
                description: 'Unsupported Media Type',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function save(): void
    {
    }

    #[OA\Get(
        path: '/lists/export',
        operationId: 'listsExport',
        tags: ['lists'],
        summary: 'Export lists config and files as ZIP',
        responses: [
            new OA\Response(
                response: 200,
                description: 'ZIP archive',
                content: new OA\MediaType(
                    mediaType: 'application/zip',
                    schema: new OA\Schema(type: 'string', format: 'binary'),
                ),
                headers: [
                    new OA\Header(header: 'Content-Disposition', schema: new OA\Schema(type: 'string')),
                ],
            ),
        ],
    )]
    public function export(): void
    {
    }

    #[OA\Post(
        path: '/lists/import',
        operationId: 'listsImport',
        tags: ['lists'],
        summary: 'Import lists config and files (ZIP or JSON)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['archive'],
                    properties: [
                        new OA\Property(
                            property: 'archive',
                            type: 'string',
                            format: 'binary',
                            description: 'ZIP or JSON file',
                        ),
                    ],
                ),
            ),
            description: 'Either upload "archive" as multipart/form-data, or send application/json with the same structure as ListsSaveRequest',
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Imported',
                content: new OA\JsonContent(ref: '#/components/schemas/ImportResponse'),
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 415,
                description: 'Unsupported Media Type',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function import(): void
    {
    }
}
