<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Infrastructure\Symfony\Ai\LlamaCppModelCatalog;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;

final class LlamaCppModelCatalogTest extends TestCase
{
    public function testBgeM3IsRegisteredAsEmbeddingsModel(): void
    {
        $model = (new LlamaCppModelCatalog())->getModel('bge-m3');

        self::assertInstanceOf(EmbeddingsModel::class, $model);
        self::assertContains(Capability::EMBEDDINGS, $model->getCapabilities());
    }

    public function testMistral7BInstructIsRegisteredAsCompletionsModel(): void
    {
        $model = (new LlamaCppModelCatalog())->getModel('mistral-7b-instruct-v0.3');

        self::assertInstanceOf(CompletionsModel::class, $model);
        self::assertContains(Capability::OUTPUT_STREAMING, $model->getCapabilities());
    }
}
