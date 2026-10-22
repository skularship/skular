<?php

namespace App\Concerns;

use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * Reads the structured payload out of a Laravel AI agent response.
 *
 * Agents implementing HasStructuredOutput receive a StructuredAgentResponse
 * from the SDK. Its `toArray()` method is the supported way to read the parsed
 * payload, so it is preferred over casting the response to JSON. Any other
 * response is decoded defensively so an unexpected plain text reply surfaces as
 * an empty payload instead of crashing the caller.
 */
trait ExtractsStructuredResponse
{
    /**
     * Extract the structured payload from an agent response.
     *
     * @return array<string, mixed>
     */
    protected function structuredPayload(AgentResponse $response): array
    {
        if ($response instanceof StructuredAgentResponse) {
            return $response->toArray();
        }

        $decoded = json_decode((string) $response, true);

        return is_array($decoded) ? $decoded : [];
    }
}
