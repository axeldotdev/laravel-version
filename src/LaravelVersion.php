<?php

namespace Axeldotdev\LaravelVersion;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class LaravelVersion
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    protected Filesystem $filesystem;
    protected string $old_version;
    protected string $new_version;
    protected ?string $changelog_mode;
    protected ?string $platform;
    protected Collection $hidden_commits;
    protected Collection $commits;

    public function handle(
        string $version,
        ?string $changelog_mode = null,
        ?string $platform = null,
        array $hidden_commits = [],
    ): int {
        $this->filesystem = new Filesystem();

        $this->ensureAppVersionExists();

        $this->old_version = config('app.version');
        $this->new_version = $version;
        $this->changelog_mode = $changelog_mode;
        $this->platform = $platform;
        $this->hidden_commits = $hidden_commits;

        $this->commits = $this->getCommits();

        if ($this->commits->isEmpty()) {
            echo __('No commit detected.');

            return self::FAILURE;
        }

        $this->updateConfig();

        if ($this->changelog_mode !== null) {
            $this->ensureChangelogExists();
            $this->updateChangelog();
        }

        $this->commitAndPush();
        $this->createTag();

        return self::SUCCESS;
    }

    protected function ensureAppVersionExists(): void
    {
        if ($this->foundInFile("'version' =>", base_path('config/app.php'))) {
            return;
        }

        $this->filesystem->replaceInFile(
            "'name' => env('APP_NAME', 'Laravel'),",
            "'name' => env('APP_NAME', 'Laravel'),\n\n    'version' => 'alpha',",
            base_path('config/app.php'),
        );
    }

    protected function getCommits(): Collection
    {
        $commits = (new Collection(explode("\n", $this->runShellCommand([
            'git', 'log',
            '--pretty=format:"%s"',
            '--no-merges',
            $this->old_version === null ? '' : "{$this->old_version}..HEAD",
        ]))))
            ->map(fn (string $commit) => trim($commit, " \"\t\n\r\0\x0B"))
            ->filter(fn (string $commit) => ! in_array($commit, $this->hidden_commits->toArray()))
            ->filter();

        if ($commits->isEmpty()) {
            $this->commits = new Collection();
        }

        if ($this->changelog_mode === 'simple') {
            $this->commits = $commits;
        }

        $groups = ['Added' => [], 'Updated' => [], 'Fixed' => [], 'Removed' => []];

        foreach ($commits as $commit) {
            if (Str::contains($commit, ['remove', 'removed', 'delete', 'deleted'])) {
                $groups['Removed'][] = $commit;
            } elseif (Str::contains($commit, ['fix', 'fixed', 'repare', 'repared'])) {
                $groups['Fixed'][] = $commit;
            } elseif (Str::contains($commit, ['update', 'updated', 'change', 'changed'])) {
                $groups['Updated'][] = $commit;
            } else {
                $groups['Added'][] = $commit;
            }
        }

        return new Collection(array_filter($groups));
    }

    protected function updateConfig(): void
    {
        $this->filesystem->replaceInFile(
            $this->old_version ?? 'alpha',
            $this->new_version,
            base_path('config/app.php'),
        );
    }

    protected function ensureChangelogExists(): void
    {
        if ($this->filesystem->exists(base_path('CHANGELOG.md'))) {
            return;
        }

        $this->filesystem->put(
            base_path('CHANGELOG.md'),
            file_get_contents(base_path('stubs/changelog.stub')),
        );
    }

    protected function updateChangelog(): void
    {
        echo __('Updating the changelog.');

        if ($this->old_version === null) {
            $this->filesystem->appendToFile(
                base_path('CHANGELOG.md'),
                $this->changelogContentCreated(),
            );
        } else {
            $this->filesystem->replaceInFile(
                "## [{$this->old_version}",
                $this->changelogContentUpdated(),
                base_path('CHANGELOG.md'),
            );
        }

        echo __('Changelog updated.');
    }

    protected function changelogContentCreated(): string
    {
        $today = today()->format('Y-m-d');

        $content = "\n";
        $content .= "## [{$this->new_version} - {$today}]()\n";
        $content .= $this->getFormatedCommits();
        $content .= "\n";

        return $content;
    }

    protected function changelogContentUpdated(): string
    {
        $compare_url = "{$this->getOriginUrl()}/compare/{$this->old_version}...{$this->new_version}";
        $today = today()->format('Y-m-d');

        $content = "## [{$this->new_version} - {$today}]({$compare_url})\n";
        $content .= $this->getFormatedCommits();
        $content .= "\n";
        $content .= "## [{$this->old_version}";

        return $content;
    }

    protected function getFormatedCommits(): string
    {
        $content = '';

        if (config('changelog.mode') === 'simple') {
            $content .= "\n";

            foreach ($this->commits as $commit) {
                $content .= "- {$commit}\n";
            }

            return $content;
        }

        foreach ($this->commits as $group => $commits) {
            $content .= "\n";
            $content .= "### {$group}\n";
            $content .= "\n";

            foreach ($commits as $commit) {
                $content .= "- {$commit}\n";
            }
        }

        return $content;
    }

    protected function getOriginUrl(): ?string
    {
        $composer_config = json_decode(file_get_contents(base_path('composer.json')));

        if (! property_exists($composer_config, 'support')
            || ! property_exists($composer_config->support, 'source')) {
            return null;
        }

        return $composer_config->support->source;
    }

    protected function commitAndPush(): void
    {
        echo __('Pushing files.');

        if ($this->changelog_mode !== null) {
            echo $this->runShellCommand(['git', 'add', 'CHANGELOG.md', 'config/app.php']);
            echo $this->runShellCommand(['git', 'commit', '-m', 'Update changelog and app version']);
        } else {
            echo $this->runShellCommand(['git', 'add', 'config/app.php']);
            echo $this->runShellCommand(['git', 'commit', '-m', 'Update app version']);
        }

        echo $this->runShellCommand(['git', 'push', 'origin', 'main']);

        echo __('Files pushed.');
    }

    protected function createTag(): void
    {
        echo __('Creating tag.');

        echo $this->runShellCommand(['git', 'tag', '-a', $this->new_version, '-m', $this->new_version]);
        echo $this->runShellCommand(['git', 'push', 'origin', $this->new_version]);

        echo __('Tag created.');
    }

    protected function runShellCommand(array $command): string
    {
        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    protected function foundInFile(string $search, string $path): bool
    {
        return Str::contains(file_get_contents($path), $search);
    }
}
