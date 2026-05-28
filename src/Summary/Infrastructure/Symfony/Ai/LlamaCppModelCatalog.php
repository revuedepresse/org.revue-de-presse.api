<?php
declare(strict_types=1);

namespace App\Summary\Infrastructure\Symfony\Ai;

use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

/**
 * Explicit catalog for the llama-server-backed llama_chat platform.
 *
 * The bundle's default FallbackModelCatalog classifies models by name
 * heuristics; pinning the entry here keeps Mistral-7B classified as a
 * CompletionsModel regardless of how the bundle's heuristics evolve.
 */
final class LlamaCppModelCatalog extends AbstractModelCatalog
{
    public function __construct()
    {
        $this->models = [
            'mistral-7b-instruct-v0.3' => [
                'class' => CompletionsModel::class,
                'capabilities' => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING],
            ],
        ];
    }
}
