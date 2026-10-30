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

    public function handle(Request $request, Closure $next): Response
    {
        $this->negotiateContentType($request);
        $this->negotiateAccept($request);

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
        if (! in_array($request->method(), ['POST'], true)) {
            return;
        }

        $contentType = AcceptHeaderItem::fromString((string) $request->headers->get('Content-Type'));

        if ($contentType->getValue() !== self::MEDIA_TYPE) {
            throw JsonApiError::unsupportedMediaType('Send request documents as '.self::MEDIA_TYPE.'.');
        }

        if (! $this->isServable($contentType)) {
            throw JsonApiError::unsupportedMediaType('The '.self::MEDIA_TYPE.' media type takes no parameters here but profile.');
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

    private function isServable(AcceptHeaderItem $mediaType): bool
    {
        return array_diff(array_keys($mediaType->getAttributes()), ['profile']) === [];
    }
}
