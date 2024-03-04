<?php

declare(strict_types=1);

namespace SyliusLabs\SuiteTagsExtension\Suite\Cli;

use Behat\Gherkin\Filter\TagFilter;
use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Suite\Cli\SuiteController;
use SyliusLabs\SuiteTagsExtension\Suite\Exception\SuiteFiltrationException;
use SyliusLabs\SuiteTagsExtension\Suite\MutableSuiteRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @see SuiteController */
final class FilteredTagsSuiteController implements Controller
{
    private MutableSuiteRegistry $registry;

    public function __construct(MutableSuiteRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function configure(Command $command): void
    {
        $command->addOption(
            '--suite-tags',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Filtrate used suites based on their configured tags.',
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        /** @var string[] $tags */
        $tags = $input->getOption('suite-tags');
        if (empty($tags) || !isset($tags[0]) || empty(trim($tags[0]))) {
            return null;
        }

        $this->processSuitesIsolation($tags[0]);

        return null;
    }

    private function processSuitesIsolation(string $inputTags): void
    {
        /** @var array<string, string[]> $config */
        foreach ($this->registry->getSuitesConfigurations() as $name => [$type, $config]) {
            if (isset($config['filters']['tags'])) {
                $suiteTags = array_map(
                    fn (string $tag) => $this->normalizeTag($tag),
                    explode('&&', $config['filters']['tags']),
                );

                if (!$this->isTagsMatchCondition($suiteTags, $inputTags)) {
                    $this->registry->removeSuiteConfiguration($name);
                }
            }
        }

        if ([] === $this->registry->getSuitesConfigurations()) {
            throw new SuiteFiltrationException(sprintf('No suites left using suite tags: %s.', $inputTags));
        }
    }

    /**
     * @see TagFilter::isTagsMatchCondition()
     *
     * @param string[] $suiteTags
     */
    private function isTagsMatchCondition(array $suiteTags, string $inputTagsString): bool
    {
        $satisfies = true;

        foreach (explode('&&', $inputTagsString) as $andTags) {
            $satisfiesComma = false;

            foreach (explode(',', $andTags) as $tag) {
                $tag = $this->normalizeTag($tag);

                if ('~' === $tag[0]) {
                    $tag = mb_substr($tag, 1, mb_strlen($tag, 'utf8') - 1, 'utf8');
                    $satisfiesComma = !in_array($tag, $suiteTags, true) || $satisfiesComma;
                } else {
                    $satisfiesComma = in_array($tag, $suiteTags, true) || $satisfiesComma;
                }
            }

            $satisfies = $satisfiesComma && $satisfies;
        }

        return $satisfies;
    }

    private function normalizeTag(string $tag): string
    {
        return str_replace('@', '', trim($tag));
    }
}
