<?php

namespace App\Base\Foundation\ModuleManifest;

/**
 * Normalizes the optional collection shapes allowed in a module manifest's
 * `extra.blb` block into the strict values consumed by ModuleManifest.
 */
class ModuleManifestNormalizer
{
    /**
     * @return array<string, string>
     */
    public function moduleMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $key => $constraint) {
            if (! is_string($key)) {
                continue;
            }

            $out[$key] = is_string($constraint) ? $constraint : '*';
        }

        return $out;
    }

    /**
     * @return array<string, array{default: bool, description: string}>
     */
    public function featureFlags(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $flags = [];

        foreach ($value as $flag => $meta) {
            if (! is_string($flag) || $flag === '') {
                continue;
            }

            if (! is_array($meta)) {
                $meta = ['default' => (bool) $meta];
            }

            $flags[$flag] = [
                'default' => (bool) ($meta['default'] ?? false),
                'description' => is_string($meta['description'] ?? null) ? (string) $meta['description'] : '',
            ];
        }

        ksort($flags);

        return $flags;
    }

    /**
     * @return list<string>
     */
    public function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($value): string => is_string($value) ? $value : '', $value),
            fn (string $value): bool => $value !== '',
        ));
    }
}
