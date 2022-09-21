<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatorTrait
{
    private TranslatorInterface $translator;

    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }
}
