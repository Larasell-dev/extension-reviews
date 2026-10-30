<?php

namespace Larasell\Reviews\Http\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A request the JSON:API spec answers with its own status, e.g. 409 for a type that doesn't
 * match the endpoint. The source names what's at fault, as in a JSON:API error object:
 * ['pointer' => '/data/type'] for a member of the document, ['header' => 'Accept'] for a header.
 */
class JsonApiError extends HttpException
{
    /**
     * @param  array{pointer: string}|array{header: string}  $source
     */
    public function __construct(int $status, string $detail, public readonly array $source)
    {
        parent::__construct($status, $detail);
    }

    public static function forbidden(string $detail, string $pointer): self
    {
        return new self(403, $detail, ['pointer' => $pointer]);
    }

    public static function conflict(string $detail, string $pointer): self
    {
        return new self(409, $detail, ['pointer' => $pointer]);
    }

    public static function notAcceptable(string $detail): self
    {
        return new self(406, $detail, ['header' => 'Accept']);
    }

    public static function unsupportedMediaType(string $detail): self
    {
        return new self(415, $detail, ['header' => 'Content-Type']);
    }
}
