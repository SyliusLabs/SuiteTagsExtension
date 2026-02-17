<?php

declare(strict_types=1);

namespace Tests\SyliusLabs\SuiteTagsExtension\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeFeature;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class TestContext implements Context
{
    private static string $workingDir;

    private static Filesystem $filesystem;

    private static string $phpBin;

    private ?Process $process = null;

    #[BeforeFeature]
    public static function beforeFeature(): void
    {
        self::$workingDir = sprintf('%s/%s/', sys_get_temp_dir(), uniqid('', true));
        self::$filesystem = new Filesystem();
        self::$phpBin = self::findPhpBinary();
    }

    #[BeforeScenario]
    public function beforeScenario(): void
    {
        self::$filesystem->remove(self::$workingDir);
        self::$filesystem->mkdir(self::$workingDir, 0777);
    }

    #[AfterScenario]
    public function afterScenario(): void
    {
        self::$filesystem->remove(self::$workingDir);
    }

    #[Given('/^a Behat configuration containing(?: "([^"]+)"|:)$/')]
    public function thereIsConfiguration(?string $content): void
    {
        if (self::isBehat4()) {
            $this->thereIsFile('behat.php', sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

return new class implements \Behat\Config\ConfigInterface {
    public function toArray(): array
    {
        $config = Yaml::parse(%s);

        foreach ($config as &$profile) {
            if (!is_array($profile) || !isset($profile['extensions'])) {
                continue;
            }

            $resolved = [];
            foreach ($profile['extensions'] as $name => $extensionConfig) {
                $resolved[$this->resolveExtensionClassName($name)] = $extensionConfig;
            }
            $profile['extensions'] = $resolved;
        }

        return $config;
    }

    private function resolveExtensionClassName(string $name): string
    {
        if (class_exists($name)) {
            return $name;
        }

        $parts = explode('\\', $name);
        $last = preg_replace('/Extension$/', '', end($parts)) . 'Extension';
        $guessed = $name . '\\ServiceContainer\\' . $last;

        if (class_exists($guessed)) {
            return $guessed;
        }

        return $name;
    }
};
PHP,
                var_export((string) $content, true),
            ));

            return;
        }

        $this->thereIsFile('behat.yml', $content);
    }

    #[Given('/^a (?:.+ |)file "([^"]+)" containing(?: "([^"]+)"|:)$/')]
    public function thereIsFile(?string $file, ?string $content): void
    {
        self::$filesystem->dumpFile(self::$workingDir . '/' . $file, (string) $content);
    }

    #[Given('/^a feature file containing(?: "([^"]+)"|:)$/')]
    public function thereIsFeatureFile(?string $content): void
    {
        $this->thereIsFile(sprintf('features/%s.feature', md5(uniqid('', true))), $content);
    }

    #[When('/^I run Behat$/')]
    public function iRunBehat(): void
    {
        $this->runBehat();
    }

    #[When('/^I run Behat with suite "([^"]+)"$/')]
    public function iRunBehatWithSuite(string $suite): void
    {
        $this->runBehat([sprintf('--suite=%s', $suite)]);
    }

    #[When('/^I run Behat with tags? "([^"]+)"$/')]
    public function iRunBehatWithTag(string $tag): void
    {
        $this->runBehat([sprintf('--tags=%s', $tag)]);
    }

    #[When('/^I run Behat with suite tags? "([^"]+)"$/')]
    public function iRunBehatWithSuiteTag(string $tag): void
    {
        $this->runBehat([sprintf('--suite-tags=%s', $tag)]);
    }

    #[Then('/^it should pass$/')]
    public function itShouldPass(): void
    {
        if (0 === $this->getProcessExitCode()) {
            return;
        }

        throw new \DomainException(
            'Behat was expecting to pass, but failed with the following output:' . PHP_EOL . PHP_EOL . $this->getProcessOutput()
        );
    }

    #[Then('/^it should pass with(?: "([^"]+)"|:)$/')]
    public function itShouldPassWith(?string $expectedOutput): void
    {
        $this->itShouldPass();
        $this->assertOutputMatches($expectedOutput);
    }

    #[Then('/^it should fail$/')]
    public function itShouldFail(): void
    {
        if (0 !== $this->getProcessExitCode()) {
            return;
        }

        throw new \DomainException(
            'Behat was expecting to fail, but passed with the following output:' . PHP_EOL . PHP_EOL . $this->getProcessOutput()
        );
    }

    #[Then('/^it should fail with(?: "([^"]+)"|:)$/')]
    public function itShouldFailWith(?string $expectedOutput): void
    {
        $this->itShouldFail();
        $this->assertOutputMatches($expectedOutput);
    }

    #[Then('/^it should end with(?: "([^"]+)"|:)$/')]
    public function itShouldEndWith(?string $expectedOutput): void
    {
        $this->assertOutputMatches($expectedOutput);
    }

    #[Then('/^its output should contain(?: "([^"]+)"|:)$/')]
    public function itsOutputShouldContain(string $expectedOutput): void
    {
        $this->assertOutputMatches($expectedOutput);
    }

    #[Then('/^it should have run (\d+) scenarios?$/')]
    public function itShouldHaveRunCountScenarios(int $count): void
    {
        $this->assertOutputMatches(sprintf('%d scenario', $count));
    }

    /** @param array<array-key, mixed> $arguments */
    private function runBehat(array $arguments = []): void
    {
        $arguments = array_merge(['--strict', '-vvv', '--no-interaction', '--lang=en'], $arguments);

        /** @phpstan-ignore-next-line */
        $this->process = new Process(array_merge([self::$phpBin, BEHAT_BIN_PATH], $arguments), self::$workingDir);
        $this->process->start();
        $this->process->wait();
    }

    private function assertOutputMatches(?string $expectedOutput): void
    {
        $pattern = '/' . preg_quote($expectedOutput ?? '', '/') . '/sm';
        $output = $this->getProcessOutput();

        $result = preg_match($pattern, $output);
        if (false === $result) {
            throw new \InvalidArgumentException('Invalid pattern given:' . $pattern);
        }

        if (0 === $result) {
            throw new \DomainException(sprintf(
                'Pattern "%s" does not match the following output:' . PHP_EOL . PHP_EOL . '%s',
                $pattern,
                $output
            ));
        }
    }

    private function getProcessOutput(): string
    {
        $this->assertProcessIsAvailable();

        return sprintf('%s%s', $this->process?->getErrorOutput(), $this->process?->getOutput());
    }

    private function getProcessExitCode(): int
    {
        $this->assertProcessIsAvailable();

        return $this->process?->getExitCode() ?? -1;
    }

    /** @throws \BadMethodCallException */
    private function assertProcessIsAvailable(): void
    {
        if (null === $this->process) {
            throw new \BadMethodCallException('Behat process cannot be found. Did you run it before making assertions?');
        }
    }

    /** @throws \RuntimeException */
    private static function findPhpBinary(): string
    {
        $phpBinary = (new PhpExecutableFinder())->find();
        if (false === $phpBinary) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }

        return $phpBinary;
    }

    private static function isBehat4(): bool
    {
        return class_exists(\Behat\Config\Config::class);
    }
}
