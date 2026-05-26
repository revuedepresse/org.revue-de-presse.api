<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

/**
 * Explicit catalog for the Ollama-backed generic platform.
 *
 * The bundle's default FallbackModelCatalog tags a model as an
 * `EmbeddingsModel` only if its name contains the substring "embed" —
 * which holds for `nomic-embed-text` / `mxbai-embed-large` but NOT for
 * `bge-m3`. Without this catalog, calling the vectorizer with `bge-m3`
 * registers it as a `CompletionsModel` and the request errors with
 * "No ModelClient registered for model "bge-m3" (CompletionsModel)…".
 *
 * Register the embedding-only models we actually use against Ollama
 * here. Add an entry per new model — pure data, no other plumbing.
 */
final class OllamaModelCatalog extends AbstractModelCatalog
{
    public function __construct()
    {
        $this->models = [
            'bge-m3' => [
                'class' => EmbeddingsModel::class,
                'capabilities' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
            ],
            'mxbai-embed-large' => [
                'class' => EmbeddingsModel::class,
                'capabilities' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
            ],
            'nomic-embed-text' => [
                'class' => EmbeddingsModel::class,
                'capabilities' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
            ],
        ];
    }
}
