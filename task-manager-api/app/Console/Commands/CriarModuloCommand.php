<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CriarModuloCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:package {name}';

    /**
     * The console command description.
     */
    protected $description = 'Cria um novo pacote (ex: admin/users)';

    /**
     * Nome do módulo em path (Admin/Users)
     */
    protected string $modulePath;

    /**
     * Namespace do módulo (Admin\\Users)
     */
    protected string $moduleNamespace;

    /**
     * Filesystem do Laravel
     */
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $rawName = trim($this->argument('name'), '/');

        // admin/users → ['admin', 'users']
        $parts = array_filter(explode('/', $rawName));

        if (empty($parts)) {
            $this->error('Nome do pacote inválido.');

            return 1;
        }

        // Admin / Users
        $studlyParts = array_map(fn ($part) => Str::studly($part), $parts);

        // Admin/Users
        $this->modulePath = implode('/', $studlyParts);

        // Admin\Users
        $this->moduleNamespace = implode('\\', $studlyParts);

        $this->info("Criando pacote: {$this->moduleNamespace}");

        $this->criarDiretorios();
        $this->criarController();

        $this->info("Pacote {$this->moduleNamespace} criado com sucesso!");

        return 0;
    }

    protected function criarDiretorios(): void
    {
        $basePath = app_path('Packages/'.$this->modulePath);

        $directories = [
            'Controllers',
            'Models',
            'Services',
            'Repositories',
            'Requests',
            'Resources',
            'Middlewares',
            'Helpers',
            'Enum',
            'Rules',
            'DataTransferObjects',
        ];

        foreach ($directories as $dir) {
            $path = "{$basePath}/{$dir}";

            if (! $this->files->isDirectory($path)) {
                $this->files->makeDirectory($path, 0755, true);
                $this->line("  ✔ Diretório criado: {$path}");
            }

            //            // Cria arquivo .gitkeep para garantir que pastas vazias sejam rastreadas pelo Git
            //            $gitkeepPath = "{$path}/.gitkeep";
            //            if (!$this->files->exists($gitkeepPath)) {
            //                $this->files->put($gitkeepPath, '');
            //                $this->line("  ✔ Arquivo .gitkeep criado: {$gitkeepPath}");
            //            }
        }
    }

    protected function criarController(): void
    {
        $moduleName = class_basename(str_replace('/', '\\', $this->modulePath));
        $controllerName = $moduleName.'Controller';

        $path = app_path(
            'Packages/'.$this->modulePath.'/Controllers/'.$controllerName.'.php'
        );

        if ($this->files->exists($path)) {
            $this->warn("Controller {$controllerName} já existe.");

            return;
        }

        $stubPath = base_path('stubs/controller.stub');

        if (! $this->files->exists($stubPath)) {
            $this->error('Arquivo stub controller.stub não encontrado.');

            return;
        }

        $stub = $this->files->get($stubPath);

        $namespace = 'App\\Packages\\'.$this->moduleNamespace.'\\Controllers';

        $stub = str_replace('{{namespace}}', $namespace, $stub);
        $stub = str_replace('{{class}}', $controllerName, $stub);

        $this->files->put($path, $stub);
        $this->line("  ✔ Controller criado: {$path}");
    }
}
