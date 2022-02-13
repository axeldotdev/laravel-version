<?php

namespace Axeldotdev\LaravelVersion\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class UpdateAppVersion extends Command
{
    /** @var string */
    protected $signature = 'app:version {version : The tag version}';

    /** @var string */
    protected $description = 'Create a Git tag and update the app version in the config.';

    protected string $old_version;

    protected string $new_version;

    protected Collection $commits;

    protected array $hidden_commits = [
        'Update changelog and app version',
        'Update app version',
        'fix',
        'wip',
    ];

    public function handle(): int
    {
        if (! $this->authorize()) {
            $this->error(__('This command can only be used in local environment.'));

            return Command::FAILURE;
        }

        $this->ensureAppVersionExists();

        $this->old_version = config('app.version');
        $this->new_version = $this->argument('version');

        $this->getCommits();

        if ($this->commits->isEmpty()) {
            $this->warn('No commit detected.');

            return Command::FAILURE;
        }

        $this->updateConfig();

        if (config('changelog.enabled')) {
            $this->ensureChangelogExists();
            $this->updateChangelog();
        }

        $this->commitAndPush();
        $this->createTag();

        return Command::SUCCESS;
    }

    protected function ensureAppVersionExists(): void
    {
        if ($this->foundInFile("'version' =>", base_path('config/app.php'))) {
            return;
        }

        $this->replaceInFile(
            "'name' => env('APP_NAME', 'Laravel'),",
            "'name' => env('APP_NAME', 'Laravel'),\n\n    'version' => 'alpha',",
            base_path('config/app.php'),
        );
    }

    protected function updateConfig(): void
    {
        $this->info(__('Replacing the app config value.'));

        $this->replaceInFile(
            $this->old_version ?? 'alpha',
            $this->new_version,
            base_path('config/app.php'),
        );

        $this->info(__('App config value replaced.'));
    }

    protected function ensureChangelogExists(): void
    {
        $filesystem = new Filesystem();

        if ($filesystem->exists(base_path('CHANGELOG.md'))) {
            return;
        }

        $filesystem->put(
            base_path('CHANGELOG.md'),
            file_get_contents(base_path('stubs/changelog.stub')),
        );
    }

    protected function updateChangelog(): void
    {
        $this->info(__('Updating the changelog.'));

        if ($this->old_version === null) {
            $this->appendToFile(
                $this->changelogContentCreated(),
                base_path('CHANGELOG.md'),
            );
        } else {
            $this->replaceInFile(
                "## [{$this->old_version}",
                $this->changelogContentUpdated(),
                base_path('CHANGELOG.md'),
            );
        }

        $this->info(__('Changelog updated.'));
    }

    protected function commitAndPush(): void
    {
        $this->info(__('Pushing files.'));

        if (config('changelog.enabled')) {
            echo $this->runShellCommand(['git', 'add', 'CHANGELOG.md', 'config/app.php']);
            echo $this->runShellCommand(['git', 'commit', '-m', 'Update changelog and app version']);
        } else {
            echo $this->runShellCommand(['git', 'add', 'config/app.php']);
            echo $this->runShellCommand(['git', 'commit', '-m', 'Update app version']);
        }

        echo $this->runShellCommand(['git', 'push', 'origin', 'main']);

        $this->info(__('Files pushed.'));
    }

    protected function createTag(): void
    {
        $this->info(__('Creating tag.'));

        echo $this->runShellCommand(['git', 'tag', '-a', $this->new_version, '-m', $this->new_version]);
        echo $this->runShellCommand(['git', 'push', 'origin', $this->new_version]);

        $this->info(__('Tag created.'));
    }

    protected function foundInFile(string $search, string $path): bool
    {
        return Str::contains(file_get_contents($path), $search);
    }

    protected function appendToFile(string $content, string $path): void
    {
        (new Filesystem())->append($path, $content);
    }

    protected function replaceInFile(string $search, string $replace, string $path): void
    {
        (new Filesystem())->replaceInFile($search, $replace, $path);
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

    protected function getCommits(): void
    {
        $commits = (new Collection(explode("\n", $this->runShellCommand([
            'git', 'log',
            '--pretty=format:"%s"',
            '--no-merges',
            $this->old_version === null ? '' : "{$this->old_version}..HEAD",
        ]))))
            ->map(fn (string $commit) => trim($commit, " \"\t\n\r\0\x0B"))
            ->filter(fn (string $commit) => ! in_array($commit, $this->hidden_commits))
            ->filter();

        if ($commits->isEmpty()) {
            $this->commits = new Collection();
        }

        if (config('changelog.mode') === 'simple') {
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

        $this->commits = new Collection(array_filter($groups));
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

    protected function runShellCommand(array $command): string
    {
        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    protected function authorize(): bool
    {
        return App::isLocal();
    }
}
