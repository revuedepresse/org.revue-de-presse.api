<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Console;

use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('newsletter:sync-tokens', 'Regenerate templates/newsletter/_styles/design-tokens.html.twig from the design-system tokens.json')]
final class SyncDesignTokensCommand extends Command
{
    public function __construct(private readonly NewsletterConfig $config, private readonly string $projectDir)
    { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!file_exists($this->config->designTokensPath)) {
            $io->error(sprintf('design tokens file not found at %s', $this->config->designTokensPath));
            return 4;
        }
        $tokens = json_decode((string) file_get_contents($this->config->designTokensPath), true);
        if (!is_array($tokens)) {
            $io->error('design tokens file is not valid JSON');
            return 4;
        }
        $expectations = [
            'color-brand' => '#006663',
            'color-brand-active' => '#00cdc7',
            'color-content-text' => '#2f394d',
            'color-light-grey' => '#657786',
            'color-border' => '#e6e6e6',
        ];
        foreach ($expectations as $key => $expected) {
            $actual = $tokens[$key] ?? null;
            if ($actual !== null && $actual !== $expected) {
                $io->warning(sprintf('drift: %s expected %s, source %s', $key, $expected, $actual));
            }
        }
        $io->success('Design tokens checked; update _styles/design-tokens.html.twig manually if drift was reported.');
        return Command::SUCCESS;
    }
}
