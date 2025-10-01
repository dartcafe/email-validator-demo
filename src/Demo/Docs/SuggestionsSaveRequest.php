<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Docs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SuggestionsSaveRequest',
    type: 'object',
    required: ['content'],
    properties: [
        new OA\Property(
            property: 'content',
            type: 'string',
            description: 'Full file content to save (one domain per line)',
            example: "gmail.com\nyahoo.com\n",
        ),
    ],
    example: [
        'content' => "gmail.com\nyahoo.com\n",
    ],
)]
final class SuggestionsSaveRequest
{
}
