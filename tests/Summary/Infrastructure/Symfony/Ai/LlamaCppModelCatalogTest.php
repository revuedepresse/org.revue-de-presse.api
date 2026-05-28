<?php
declare(strict_types=1);

namespace App\Tests\Summary\Infrastructure\Symfony\Ai;

use App\Summary\Infrastructure\Symfony\Ai\LlamaCppModelCatalog;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Capability;

final class LlamaCppModelCatalogTest extends TestCase
{
    public function testMistral7BInstructIsRegisteredAsCompletionsModel(): void
    {
        $model = (new LlamaCppModelCatalog())->getModel('mistral-7b-instruct-v0.3');

        self::assertInstanceOf(CompletionsModel::class, $model);
        self::assertContains(Capability::OUTPUT_STREAMING, $model->getCapabilities());
    }
}
