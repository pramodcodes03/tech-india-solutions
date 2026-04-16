<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GenerateRoutesDoc extends Command
{
    protected $signature = 'docs:generate-routes';

    protected $description = 'Generate a markdown routes reference document from all named routes';

    public function handle()
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->getName())
            ->map(fn ($route) => [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $this->resolveAction($route->getActionName()),
            ])
            ->sortBy('name');

        $grouped = $routes->groupBy(function ($route) {
            $parts = explode('.', $route['name']);
            if (count($parts) >= 2) {
                return implode('.', array_slice($parts, 0, 2));
            }

            return $parts[0] ?? 'other';
        });

        $markdown = "# Routes Reference\n\n";
        $markdown .= '> Auto-generated on '.now()->toDateTimeString()."\n\n";

        foreach ($grouped as $prefix => $groupRoutes) {
            $title = Str::of($prefix)
                ->replace('.', ' ')
                ->replace('_', ' ')
                ->title();

            $markdown .= "## {$title}\n\n";
            $markdown .= "| Method | URI | Name | Controller |\n";
            $markdown .= "|--------|-----|------|------------|\n";

            foreach ($groupRoutes as $route) {
                $markdown .= "| {$route['method']} | /{$route['uri']} | {$route['name']} | {$route['action']} |\n";
            }

            $markdown .= "\n";
        }

        $paths = [
            resource_path('docs/routes-reference.md'),
            base_path('docs/routes-reference.md'),
        ];

        foreach ($paths as $path) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $markdown);
            $this->info("Written to: {$path}");
        }

        $this->info('Routes reference generated successfully.');

        return Command::SUCCESS;
    }

    protected function resolveAction(string $action): string
    {
        if ($action === 'Closure') {
            return 'Closure';
        }

        $parts = explode('@', class_basename(str_replace('\\', '/', $action)));

        return implode('@', $parts);
    }
}
