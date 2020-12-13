<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatorTrait
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @param TranslatorInterface $translator
     *
     * @return $this
     */
    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }
}