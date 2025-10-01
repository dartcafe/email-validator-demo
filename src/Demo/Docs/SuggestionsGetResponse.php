<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Docs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SuggestionsGetResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'path', type: 'string', example: 'config/suggestions.txt'),
        new OA\Property(
            property: 'content',
            type: 'string',
            description: 'One domain per line; lines starting with # are comments',
            example: "gmail.com\nyahoo.com\n",
        ),
    ],
    example: [
        'path'    => 'config/suggestions.txt',
        'content' => "gmail.com\nyahoo.com\n",
    ],
)]
final class SuggestionsGetResponse
{
}
