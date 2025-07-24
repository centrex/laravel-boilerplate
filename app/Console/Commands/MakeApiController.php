<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Create a complete API endpoint with controller, resource, and optional supporting files
 * 
 * Examples:
 * 
 * # Create complete v1 API for Products (model, migration, factory, seeder, controller, resource, requests)
 * php artisan make:api Product --vs=v1 --all
 * 
 * # Create v2 API for Users with specific components
 * php artisan make:api User --vs=v2 --controller --resource --requests
 * 
 * # Create unversioned API for Customers
 * php artisan make:api Customer --all
 * 
 * # Create just the controller and resource for v3 Posts
 * php artisan make:api Post --vs=v3 --controller --resource
 * 
 * # See help
 * php artisan help make:api
 */
class MakeApiController extends Command
{
    use \App\Traits\ApiResponser;

    protected $signature = 'make:api {name : The name of the model} 
                            {--vs= : API version (e.g., v1)}
                            {--a|all : Generate model, migration, factory, seeder, and controller} 
                            {--auth : Add authentication middleware to routes}
                            {--m|model : Generate model} 
                            {--f|factory : Generate factory} 
                            {--s|seed : Generate seeder} 
                            {--c|controller : Generate controller} 
                            {--r|resource : Generate resource} 
                            {--p|policy : Generate policy} 
                            {--requests : Generate form request classes}';

    protected $description = 'Create a new versioned API controller with API Resource and ApiResponser trait';

    public function handle()
    {
        $modelName = $this->argument('name');
        $version = $this->option('vs') ? 'V' . strtoupper(str_replace(['v', 'V'], '', $this->option('vs'))) : '';
        $modelPlural = Str::plural($modelName);
        $controllerName = "{$modelName}Controller";
        $resourceName = "{$modelName}Resource";
        $collectionName = "{$modelName}Collection";

        // Generate model if requested
        if ($this->option('all') || $this->option('model')) {
            $this->call('make:model', [
                'name' => $modelName,
                '--migration' => true,
                '--factory' => $this->option('all') || $this->option('factory'),
                '--seed' => $this->option('all') || $this->option('seed'),
            ]);
        }

        // Generate API resource with versioning if specified
        if ($this->option('all') || $this->option('resource')) {
            $resourcePath = $version ? "Http/Resources/{$version}" : "Http/Resources";
            $this->ensureDirectoryExists(app_path($resourcePath));
            
            $this->call('make:resource', [
                'name' => ($version ? "{$version}/" : '') . $resourceName
            ]);
            
            $this->call('make:resource', [
                'name' => ($version ? "{$version}/" : '') . $collectionName,
                '--collection' => true
            ]);
            
            $this->info("API Resource {$resourceName} created successfully in {$version}.");
        }

        // Generate policy if requested
        if ($this->option('all') || $this->option('policy')) {
            $this->call('make:policy', [
                'name' => "{$modelName}Policy",
                '--model' => $modelName
            ]);
            $this->info("Policy created successfully.");
        }

        // Generate form requests with versioning if specified
        if ($this->option('all') || $this->option('requests')) {
            $requestPath = $version ? "Http/Requests/{$version}" : "Http/Requests";
            $this->ensureDirectoryExists(app_path($requestPath));
            
            $this->call('make:request', [
                'name' => ($version ? "{$version}/" : '') . "Store{$modelName}Request"
            ]);
            
            $this->call('make:request', [
                'name' => ($version ? "{$version}/" : '') . "Update{$modelName}Request"
            ]);
            
            $this->info("Form requests created successfully in {$version}.");
        }

        // Generate controller with versioning
        if ($this->option('all') || $this->option('controller')) {
            $controllerPath = $version ? "Http/Controllers/{$version}" : "Http/Controllers";
            $this->ensureDirectoryExists(app_path($controllerPath));
            
            $fullControllerPath = app_path("{$controllerPath}/{$controllerName}.php");
            
            if (File::exists($fullControllerPath)) {
                $this->error("Controller {$controllerName} already exists in {$version}!");
                return;
            }

            $stub = $this->generateControllerStub($modelName, $resourceName, $collectionName, $version);
            File::put($fullControllerPath, $stub);

            $this->info("API Controller {$controllerName} created successfully in {$version}.");
            $this->info("Don't forget to register the routes in your api.php file:");
            
            $routePrefix = $version ? strtolower($version) . '/' : '';
            $this->line("Route::apiResource('{$routePrefix}".Str::kebab($modelPlural)."', \\App\\Http\\Controllers".($version ? "\\{$version}" : '')."\\{$controllerName}::class);");
        }

        // Generate route
        if ($this->option('auth')) {
            $this->line("Route::prefix('{$routePrefix}')->middleware('auth:sanctum')->group(function () {");
            $this->line("    Route::apiResource('".Str::kebab($modelPlural)."', \\App\\Http\\Controllers".($version ? "\\{$version}" : '')."\\{$controllerName}::class);");
            $this->line("});");
        } else {
            $this->line("Route::apiResource('{$routePrefix}".Str::kebab($modelPlural)."', \\App\\Http\\Controllers".($version ? "\\{$version}" : '')."\\{$controllerName}::class);");
        }
    }

    protected function ensureDirectoryExists($path)
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function generateControllerStub($modelName, $resourceName, $collectionName, $version)
    {
        $modelVariable = Str::camel($modelName);
        $modelPlural = Str::plural($modelVariable);
        $modelClass = "App\\Models\\{$modelName}";
        $versionNamespace = $version ? "\\{$version}" : '';
        $storeRequest = "Store{$modelName}Request";
        $updateRequest = "Update{$modelName}Request";
        
        // Include version in resource and request paths if specified
        $resourcePath = $version ? "{$version}\\" : '';
        $requestPath = $version ? "{$version}\\" : '';

        return <<<EOT
<?php

namespace App\Http\Controllers{$versionNamespace};

use App\Models\\{$modelName};
use App\Http\Resources\\{$resourcePath}{$resourceName};
use App\Http\Resources\\{$resourcePath}{$collectionName};
use App\Http\Requests\\{$requestPath}{$storeRequest};
use App\Http\Requests\\{$requestPath}{$updateRequest};
use Illuminate\Http\Request;

class {$modelName}Controller extends Controller
{
    use \App\Traits\ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        \${$modelPlural} = {$modelName}::paginate(10);
        return \$this->paginatedResponse(
            new {$collectionName}(\${$modelPlural}),
            '{$modelName} list retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\\{$requestPath}{$storeRequest}  \$request
     * @return \Illuminate\Http\Response
     */
    public function store({$storeRequest} \$request)
    {
        \${$modelVariable} = {$modelName}::create(\$request->validated());
        return \$this->successResponse(
            new {$resourceName}(\${$modelVariable}),
            '{$modelName} created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\\{$modelName}  \${$modelVariable}
     * @return \Illuminate\Http\Response
     */
    public function show({$modelName} \${$modelVariable})
    {
        return \$this->successResponse(
            new {$resourceName}(\${$modelVariable}),
            '{$modelName} retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\\{$requestPath}{$updateRequest}  \$request
     * @param  \App\Models\\{$modelName}  \${$modelVariable}
     * @return \Illuminate\Http\Response
     */
    public function update({$updateRequest} \$request, {$modelName} \${$modelVariable})
    {
        \${$modelVariable}->update(\$request->validated());
        return \$this->successResponse(
            new {$resourceName}(\${$modelVariable}),
            '{$modelName} updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\\{$modelName}  \${$modelVariable}
     * @return \Illuminate\Http\Response
     */
    public function destroy({$modelName} \${$modelVariable})
    {
        \${$modelVariable}->delete();
        return \$this->noContentResponse();
    }
}
EOT;
    }
}