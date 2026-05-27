<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

/**
 * Explicit catalog for the llama-server-backed generic platforms.
 *
 * The bundle's default FallbackModelCatalog classifies a model as
 * EmbeddingsModel only if its name contains "embed" — true for
 * `mxbai-embed-large`/`nomic-embed-text` but NOT for `bge-m3`. Without
 * this catalog, vectorizer calls against `bge-m3` register it as a
 * CompletionsModel and fail with "No ModelClient registered for model
 * 'bge-m3' (CompletionsModel)…".
 *
 * Both ai.platform.generic.llama_chat and ai.platform.generic.llama_embed
 * share this catalog (ai.yaml wires both to the same class) — the
 * `supports_completions` / `supports_embeddings` flags on each platform
 * already constrain which models that platform exposes.
 */
final class LlamaCppModelCatalog extends AbstractModelCatalog
{
    public function __construct()
    {
        $this->models = [
            'bge-m3' => [
                'class' => EmbeddingsModel::class,
                'capabilities' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
            ],
            'mistral-7b-instruct-v0.3' => [
                'class' => CompletionsModel::class,
                'capabilities' => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING],
            ],
        ];
    }
}
