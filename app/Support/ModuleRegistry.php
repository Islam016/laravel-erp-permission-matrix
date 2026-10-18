<?php

namespace App\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

/**
 * Reads config/modules.php and turns it into permission names and a
 * role x permission matrix.
 */
class ModuleRegistry
{
    /**
     * @param  array{actions: array<string, array<string, string>>, modules: array<string, array{label: array<string, string>, actions: list<string>}>, super_roles?: list<string>}  $config
     */
    public function __construct(private readonly array $config)
    {
        foreach ($this->config['modules'] as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                if (! isset($this->config['actions'][$action])) {
                    throw new InvalidArgumentException("Module [{$module}] uses unknown action [{$action}].");
                }
            }
        }
    }

    public static function permissionName(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }

    /**
     * @return array<string, array{label: array<string, string>, actions: list<string>}>
     */
    public function modules(): array
    {
        return $this->config['modules'];
    }

    /**
     * Every action used by at least one module, in config order.
     *
     * @return list<string>
     */
    public function actions(): array
    {
        $used = collect($this->config['modules'])->pluck('actions')->flatten()->unique();

        return collect(array_keys($this->config['actions']))
            ->filter(fn (string $action) => $used->contains($action))
            ->values()
            ->all();
    }

    public function actionLabel(string $action, string $locale = 'en'): string
    {
        return $this->config['actions'][$action][$locale] ?? $this->config['actions'][$action]['en'] ?? $action;
    }

    public function moduleLabel(string $module, string $locale = 'en'): string
    {
        $label = $this->config['modules'][$module]['label'] ?? [];

        return $label[$locale] ?? $label['en'] ?? $module;
    }

    public function supports(string $module, string $action): bool
    {
        return in_array($action, $this->config['modules'][$module]['actions'] ?? [], true);
    }

    /**
     * All permission names defined by the registry.
     *
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        return collect($this->config['modules'])
            ->flatMap(fn (array $definition, string $module) => collect($definition['actions'])
                ->map(fn (string $action) => self::permissionName($module, $action)))
            ->values();
    }

    public function isSuperRole(Role|string $role): bool
    {
        $name = $role instanceof Role ? $role->name : $role;

        return in_array($name, $this->config['super_roles'] ?? [], true);
    }

    /**
     * The checkbox grid for a role: module => action => granted?
     * Actions a module does not support are null.
     *
     * @return array<string, array<string, bool|null>>
     */
    public function matrixFor(Role $role): array
    {
        $granted = $role->permissions->pluck('name')->flip();

        $matrix = [];

        foreach ($this->modules() as $module => $definition) {
            foreach ($this->actions() as $action) {
                $matrix[$module][$action] = $this->supports($module, $action)
                    ? $granted->has(self::permissionName($module, $action))
                    : null;
            }
        }

        return $matrix;
    }

    /**
     * Convert submitted matrix input (module => [actions]) into permission
     * names, ignoring anything the registry does not define.
     *
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    public function permissionsFromInput(array $input): array
    {
        $names = [];

        foreach ($input as $module => $actions) {
            foreach ((array) $actions as $action) {
                if (is_string($module) && is_string($action) && $this->supports($module, $action)) {
                    $names[] = self::permissionName($module, $action);
                }
            }
        }

        return array_values(array_unique($names));
    }
}
