<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
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
            // Chat completion. Both Gemma 2 sizes registered so swapping
            // is a single edit in services.chat.yaml (`$model: gemma2:2b`
            // vs `$model: gemma2:9b`).
            //
            // gemma2:2b — ~1.6 GB at idle, fits a default Docker Desktop
            //   VM with bge-m3 also loaded. Default in services.chat.yaml.
            // gemma2:9b — ~5.4 GB at idle. Needs ~10 GB container RAM
            //   total (with bge-m3); bump Docker Desktop's VM memory to
            //   12 GB+ before switching, otherwise the llama runner gets
            //   OOM-killed and Ollama returns 500.
            'gemma2:2b' => [
                'class' => CompletionsModel::class,
                'capabilities' => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING],
            ],
            'gemma2:9b' => [
                'class' => CompletionsModel::class,
                'capabilities' => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING],
            ],
        ];
    }
}
