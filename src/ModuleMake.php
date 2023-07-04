<?php

/**
 * @author Ahmed Barakat <abarakat4202@gmail.com>
 */

namespace Westore\ModuleGenerator;

use ErrorException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ModuleMake
{
    private string $baseDir;
    private string $module;
    private string $fileType;
    private string $fileName;

    private array $output = [];
 
    public function __construct()
    {
    }

    public function handle(string $type, string $name, string $module, string $baseDir = 'Modules', ?string $flags = null): array
    {
        $this->baseDir = Str::of($baseDir)->studly()->toString();

        $this->fileType = strtolower($type);
        
        $this->module = Str::of($module)->studly()->singular()->toString();
        
        $this->fileName = $this->enhancedFileName($name);
        
        $allowedTypesKeys = array_keys(config('module-generator.allowed_types'));

        if (! in_array($this->fileType, $allowedTypesKeys)) 
        {
            throw new ErrorException('Not supported type');
        }

        return $this->make($this->fileType, $this->fileName, $flags);
    }

    protected function enhancedFileName(string $name): string
    {
        $name = Str::of($name)->studly()->toString();
        
        if (in_array($this->fileType, config('module-generator.dont_rename'))) {
            return $name;
        }

        $type = Str::singular(ucfirst($this->fileType));
        
        return strpos($name, $type) !== false ? $name : $name.$type;
    }    

    protected function make(string $fileType, string $fileName, string $flags = null): array
    {
        if( file_exists($this->getStubPath($fileType)) )
        {
            return $this->makeFromStub($fileType, $fileName);
        }

        if( $fileType == 'filter' )
        {
            return $this->makeFilter($fileType, $fileName);
        }        

        return $this->makeUsingLaravel($fileType, $fileName, $flags);
    }

    protected function makeFromStub(string $type, string $fileName): array
    {
        $stubPath = $this->getStubPath($type);

        $content = file_get_contents($stubPath);

        $content = $this->replaceStubVaraibles($content, $type, $fileName, $this->module);

        $filePath = $this->getFilePath($type, $fileName, $this->module);
        
        if( ! file_exists($filePath) )
        {
            if(!file_exists( dirname($filePath) ))
            {
                mkdir(dirname($filePath), 0755, true);
            }

            file_put_contents($filePath, $content);
        }

        $this->output('Created Succefully ' . $this->getNamespace($type, $fileName));
        
        $this->makeRelatedTypes($type, $fileName, $this->module) ?? [];

        return $this->output;
    }    

    protected function getStubPath(string $fileType): string 
    {
        $ds = DIRECTORY_SEPARATOR;
        
        return sprintf(__DIR__."%sstubs%s$fileType.stub", $ds, $ds);
    }    

    protected function setNamespace(string $filePath, string $namespace): void
    {
        $content = file_get_contents($filePath);

        $content = preg_replace("/namespace .*;/", "namespace $namespace;", $content);
        
        file_put_contents($filePath, $content);
    }

    protected function makeUsingLaravel(string $fileType, string $fileName, $flags = null): array
    {
        
        $namespace = $this->getNamespace($fileType);

        if( $fileType == 'factory' || $fileType == 'seeder' )
        {
            $namespace = '../../' . $namespace;
        }
        
        $namespace = str_replace('\\', '/', $namespace);
        // dd("make:$fileType $namespace/$fileName $flags");
        $this->output(Artisan::call("make:$fileType $namespace/$fileName $flags"));

        $this->makeRelatedTypes($fileType, $fileName, $this->module);

        if( $fileType =='factory' || $fileType == 'seeder' )
        {
            $this->setNamespace($this->getFilePath($fileType, $fileName, $this->module), $this->getNamespace($fileType, $this->module));
        }

        $this->output('Created Succefully ' . $this->getNamespace($fileType, $fileName));

        return $this->output;
    }

    public function makeFilter(string $fileType, string $fileName, $flags = null): array
    {
        $namespace = "../../" . $this->getNamespace($fileType, $this->module);

        $namespace = str_replace('\\', '/', $namespace);

        $this->output(Artisan::call("model:$fileType $namespace/$fileName $flags"));

        $this->setNamespace($this->getFilePath($fileType, $fileName, $this->module), $this->getNamespace($fileType, $this->module));
        
        $this->output('Created Succefully ' . $this->getNamespace($fileType, $fileName));

        return $this->output;
    }

    protected function replaceStubVaraibles(string $content, string $fileType, string $fileName): string
    {
        $namespace = $this->getNamespace($fileType, null);

        $replace = [
            '{{ NAMESPACE }}' => $namespace,
            '{{ FILENAME }}' => $fileName,
            '{{ SERVICE }}' => $this->getRelatedName($fileName, $fileType, 'service'),
            '{{ REQUEST }}' => $this->getRelatedName($fileName, $fileType, 'request'),
            '{{ RESPONDER }}' => $this->getRelatedName($fileName, $fileType, 'responder'),
            '{{ CLASSNAME }}' => $this->getRelatedName($fileName, $fileType, 'class'),
            '{{ REPOSITORYNAME }}' => $this->getRelatedName($fileName, $fileType, 'repository'),
            '{{ SERVICENAMESPACE }}' => $this->getNamespace('service'),
            '{{ REPOSITORYNAMESPACE }}' => $this->getNamespace('repository'),
            '{{ RESPONDERNAMESPACE }}' => $this->getNamespace('responder'),
            '{{ REQUESTNAMESPACE }}' => $this->getNamespace('request'),
            '{{ CLASSNAMESPACE }}' => $this->getNamespace('class'),
        ];

        $content = str_replace(
            array_keys($replace), 
            $replace,
            $content
        );
        return $content;
    }

    protected function getFilePath(string $fileType, string $fileName): string
    {
        $typeDir = config('module-generator.allowed_types')[$fileType];

        $typeDir = str_replace('\\', DIRECTORY_SEPARATOR, $typeDir);
        
        $pathArray = array_filter([$this->baseDir, $this->module, $typeDir, $fileName.'.php']);

        $filePath = app_path(implode(DIRECTORY_SEPARATOR, $pathArray));

        return $filePath;
    }

    protected function getNamespace(string $fileType, ?string $fileName = null): string
    {
        $typeFolder = config('module-generator.allowed_types')[$fileType] ?? Str::plural(ucfirst($fileType));
        
        $pathArray = array_filter(['App', $this->baseDir, $this->module, $typeFolder, $fileName]);

        $filePath = implode('\\', $pathArray);

        return $filePath;
    }

    protected function makeRelatedTypes(string $fileType, string $fileName): ?array
    {
        $relatedTypes = array_values(
            array_filter(config('module-generator.related_types'), fn($relatedTypesGroup) => 
                in_array($fileType, explode(',', $relatedTypesGroup))
            )
        )[0] ?? null;
        
        if( ! $relatedTypes )
        {
            return null;
        }
        
        $relatedTypes = explode(',', $relatedTypes);

        foreach( $relatedTypes as $relatedType )
        {
            if( $relatedType == $fileType ) continue;

            $relatedName = $this->getRelatedName($fileName, $fileType, $relatedType);

            if( ! file_exists($this->getFilePath($relatedType, $relatedName)) )
            {
                $this->make($relatedType, $relatedName);
            }
        }

        return $this->output;
    }

    protected function getRelatedName(string $fileName, string $fileType, string $relatedType)
    {
        $replaceWith = in_array($relatedType, config('module-generator.dont_rename')) ? '' : ucfirst($relatedType);
        
        if( Str::of($fileName)->endsWith(ucfirst($fileType)) )
        {
            $relatedName = Str::of($fileName)->replaceLast(ucfirst($fileType), $replaceWith);
        }
        else
        {
            $relatedName = $fileName . $replaceWith;
        }

        return $relatedName;
    }

    private function output(string|array|null $messages)
    {
   
        if( $messages )
        {
            $messages = Arr::wrap($messages);

            $this->output = array_merge($this->output, $messages);
        }

        return $this->output;
    }
}
