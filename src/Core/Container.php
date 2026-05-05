<?php

namespace App\Core;

use Closure;
use Exception;
use ReflectionClass;

/**
 * DI Container simples com suporte a singletons e auto-wiring via Reflection.
 */
class Container
{
    /** @var array<string, Closure> Bindings registrados */
    private array $bindings = [];

    /** @var array<string, mixed> Instâncias de singletons */
    private array $instances = [];

    /**
     * Registra um binding no container (nova instância a cada chamada).
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $abstract = ltrim($abstract, '\\');
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Registra um singleton (mesma instância em toda a aplicação).
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $abstract = ltrim($abstract, '\\');
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        };
    }

    /**
     * Resolve uma dependência pelo seu nome/classe.
     * Tenta o binding registrado first; se não houver, faz auto-wiring via Reflection.
     *
     * @throws Exception se não for possível resolver
     */
    public function make(string $abstract): mixed
    {
        $abstract = ltrim($abstract, '\\');

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        return $this->build($abstract);
    }

    /**
     * Alias para make() em conformidade com PSR-11.
     */
    public function get(string $id): mixed
    {
        return $this->make($id);
    }


    /**
     * Auto-wiring: instancia a classe resolvendo as dependências do construtor.
     *
     * @throws Exception
     */
    private function build(string $class): mixed
    {
        $class = ltrim($class, '\\');
        if (!class_exists($class)) {
            throw new Exception("Container: não foi possível resolver '{$class}'. Registre um binding.");
        }

        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Container: '{$class}' não é instanciável.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $class();
        }

        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type && !$type->isBuiltin()) {
                $params[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                throw new Exception("Container: não foi possível resolver o parâmetro '{$param->getName()}' em '{$class}'.");
            }
        }

        return $reflector->newInstanceArgs($params);
    }

    /**
     * Verifica se um binding está registrado.
     */
    public function has(string $abstract): bool
    {
        $abstract = ltrim($abstract, '\\');
        return isset($this->bindings[$abstract]);
    }
}
