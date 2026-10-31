<?php

namespace Larasell\Reviews\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Larasell\Reviews\Http\Exceptions\JsonApiError;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\AcceptHeaderItem;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content negotiation as the JSON:API spec requires it, and the version the API follows.
 * The API supports no extensions; profiles are allowed and ignored.
 */
class JsonApi
{
    public const string MEDIA_TYPE = 'application/vnd.api+json';

    public const string VERSION = '1.1';

    /**
     * @param  string  ...$includable  The relationship paths clients can name in ?include=.
     */
    public function handle(Request $request, Closure $next, string ...$includable): Response
    {
        $this->negotiateContentType($request);
        $this->negotiateAccept($request);
        $this->checkInclude($request, $includable);

        // Laravel keeps the version in a static property shared by every JSON:API resource, so
        // it is set only while the API answers and other resources in the app keep their own.
        $previous = JsonApiResource::$jsonApiInformation;
        JsonApiResource::configure(version: self::VERSION);

        try {
            return $next($request);
        } finally {
            JsonApiResource::$jsonApiInformation = $previous;
        }
    }

    /**
     * Request documents must be sent as JSON:API, with no media type parameters but profile.
     */
    private function negotiateContentType(Request $request): void
    {
        if (! in_array($request->method(), ['POST', 'PATCH'], true)) {
            return;
        }

        $contentType = AcceptHeaderItem::fromString((string) $request->headers->get('Content-Type'));

        if ($contentType->getValue() !== self::MEDIA_TYPE) {
            throw JsonApiError::unsupportedMediaType('Send request documents as '.self::MEDIA_TYPE.'.');
        }

        if (! $this->isServable($contentType)) {
            throw JsonApiError::unsupportedMediaType('The '.self::MEDIA_TYPE.' media type takes no parameters here but profile.');
        }

        // Otherwise validation would report every member as missing, which hides the actual mistake.
        if (! is_object(json_decode($request->getContent()))) {
            throw JsonApiError::badRequest('The request document must be a JSON object.');
        }
    }

    /**
     * A client that accepts JSON:API must accept at least one instance this API can serve.
     * Clients that don't name JSON:API, e.g. with *∕*, get it anyway.
     */
    private function negotiateAccept(Request $request): void
    {
        $instances = array_filter(
            AcceptHeader::fromString($request->headers->get('Accept'))->all(),
            fn (AcceptHeaderItem $item): bool => $item->getValue() === self::MEDIA_TYPE,
        );

        if ($instances !== [] && array_filter($instances, $this->isServable(...)) === []) {
            throw JsonApiError::notAcceptable('Accept '.self::MEDIA_TYPE.' without parameters but profile.');
        }
    }

    /**
     * The spec requires a 400 for an include path the server can't serve, rather than leaving it
     * out. It's checked before the request is handled, so a create or update doesn't happen first.
     *
     * @param  array<string>  $includable
     */
    private function checkInclude(Request $request, array $includable): void
    {
        if (! $request->query->has('include')) {
            return;
        }

        // ConvertEmptyStringsToNull turns an empty ?include= into null.
        $include = $request->query->all()['include'] ?? '';
        $paths = is_string($include) && $include !== '' ? explode(',', $include) : [];

        if (! is_string($include) || array_diff($paths, $includable) !== []) {
            throw JsonApiError::badRequestParameter('Include only '.implode(', ', $includable).'.', 'include');
        }
    }

    private function isServable(AcceptHeaderItem $mediaType): bool
    {
        return array_diff(array_keys($mediaType->getAttributes()), ['profile']) === [];
    }
}
