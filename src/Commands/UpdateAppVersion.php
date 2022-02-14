<?php

namespace Axeldotdev\LaravelVersion\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Axeldotdev\LaravelVersion\Facades\LaravelVersion;

class UpdateAppVersion extends Command
{
    /** @var string */
    protected $signature = 'app:version {version : The tag version}
                                {--c|changelog=simple : The changelog format (none, simple, group)}
                                {--p|platform= : The platform used to store your code (github, gitlab, bitbucket)}';

    /** @var string */
    protected $description = 'Create a Git tag and update the app version in the config.';

    public function handle(): int
    {
        if (! $this->authorize()) {
            $this->error(__('This command can only be used in local environment.'));

            return Command::FAILURE;
        }

        $data = $this->data();

        LaravelVersion::handle(...$data);

        return Command::SUCCESS;
    }

    protected function authorize(): bool
    {
        return App::isLocal();
    }

    private function data(): array
    {
        return [
            'version' => $this->argument('version') ?? $this->ask('What is the new version?'),
            'changelog_mode' => $this->changelogMode(),
            'platform' => $this->platform(),
            'hidden_commits' => config('version.commits.hidden'),
        ];
    }

    private function changelogMode(): ?string
    {
        $option = $this->option('changelog');

        if ($option === 'none') {
            return null;
        }

        if (in_array($option, ['simple', 'group'])) {
            return $option;
        }

        if (config('version.changelog.enabled')) {
            return config('version.changelog.mode');
        }

        return null;
    }

    private function platform(): ?string
    {
        $option = $this->option('platform');

        if ($option === 'none') {
            return null;
        }

        if (in_array($option, ['bitbucket', 'github', 'gitlab'])) {
            return $option;
        }

        if (config('version.platform.enabled')) {
            return config('version.platform.name');
        }

        return null;
    }
}
