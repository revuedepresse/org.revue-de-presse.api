<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Translation;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator implements TranslatorInterface
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @param string      $id
     * @param array       $parameters
     * @param string|null $domain
     * @param string|null $locale
     *
     * @return string
     */
    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null)
    {
        try {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        } catch (\Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['stacktrace' => $exception->getTraceAsString()]
            );
        }
    }
}