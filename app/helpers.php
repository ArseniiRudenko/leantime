<?php

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Core\Configuration\AppSettings;
use Leantime\Core\Language;
use Leantime\Core\Support\Build;
use Leantime\Core\Support\Cast;
use Leantime\Core\Support\DateTimeHelper;
use Leantime\Core\Support\Format;
use Leantime\Core\Support\FromFormat;
use Leantime\Core\Support\Mix;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (! function_exists('__')) {
    /**
     * Translate a string.
     *
     * @throws BindingResolutionException
     */
    function __(string $index, string $default = ''): string
    {
        return app()->make(Language::class)->__(index: $index, default: $default);
    }
}

if (! function_exists('array_sort')) {
    /**
     * sort array of arrqays by value
     *
     * @param  string  $sortyBy
     */
    function array_sort(array $array, mixed $sortyBy): array
    {

        if (is_string($sortyBy)) {
            $collection = collect($array);

            $sorted = $collection->sortBy($sortyBy, SORT_NATURAL);

            return $sorted->values()->all();
        } else {
            return \Illuminate\Support\Collection::make($array)->sortBy($sortyBy)->all();
        }
    }
}

if (! function_exists('do_once')) {
    /**
     * Execute a callback only once.
     */
    function do_once(string $key, Closure $callback, bool $across_requests = false): void
    {
        $key = "do_once_{$key}";

        if ($across_requests) {
            if (session()->exists('do_once') === false) {
                session(['do_once' => []]);
            }

            if (session('do_once.'.$key) ?? false) {
                return;
            }

            session(['do_once.'.$key => true]);
        } else {
            static $do_once;
            $do_once ??= [];

            if ($do_once[$key] ?? false) {
                return;
            }

            $do_once[$key] = true;
        }

        $callback();
    }
}

if (! function_exists('build')) {
    /**
     * Turns any object into a builder object
     *
     **/
    function build(object $object): Build
    {
        return new Build($object);
    }
}

if (! function_exists('format')) {
    /**
     * Returns a format object to format string values
     *
     * @param string|int|float|DateTime|Carbon|null $value
     * @param string|int|float|DateTime|CarbonInterface|null $value2
     * @param FromFormat|null $fromFormat
     * @return Format|string
     */
    function format(string|int|float|null|\DateTime|\Carbon\CarbonInterface $value, string|int|float|null|\DateTime|\Carbon\CarbonInterface $value2 = null, ?FromFormat $fromFormat = FromFormat::DbDate): Format|string
    {
        return new Format($value, $value2, $fromFormat);
    }
}

if (! function_exists('cast')) {
    /**
     * Casts a variable to a different type if possible.
     *
     * @param mixed $source
     * @param string $classOrType
     * @param array $constructParams
     * @param array $mappings Make sure certain sub properties are casted to specific types.
     * @return mixed The casted object, or throws an exception on failure.
     *
     * @throws ReflectionException On serialization errors.
     */
    function cast(mixed $source, string $classOrType, array $constructParams = [], array $mappings = []): mixed
    {
        if (in_array($classOrType, ['int', 'integer', 'float', 'string', 'str', 'bool', 'boolean', 'object', 'stdClass', 'array'])) {
            return Cast::castSimple($source, $classOrType);
        }

        if (enum_exists($classOrType)) {
            return Cast::castEnum($source, $classOrType);
        }

        // Convert string to date if required.
        if (is_string($source) && is_a($classOrType, CarbonInterface::class, true)) {
            return Cast::castDateTime($source);
        }

        return (new Cast($source))->castTo($classOrType, $constructParams, $mappings);
    }
}

if (! function_exists('dtHelper')) {
    /**
     * Get a singleton instance of the DateTimeHelper class.
     *
     * @throws BindingResolutionException
     */
    function dtHelper(): DateTimeHelper
    {
        if (! app()->bound(DateTimeHelper::class)) {
            app()->singleton(DateTimeHelper::class);
        }

        return app()->make(DateTimeHelper::class);
    }
}

if (! function_exists('redirect')) {
    /**
     * Get an instance of the redirector.
     *
     * @param null $url
     * @param int $http_response_code
     * @param array $headers
     * @param null $secure
     * @return RedirectResponse
     */
    function redirect($url = null, int $http_response_code = 302, array $headers = [], $secure = null): RedirectResponse
    {
        return new RedirectResponse(
            trim(preg_replace('/\s\s+/', '', strip_tags($url))),
            $http_response_code
        );
    }
}

if (! function_exists('currentRoute')) {
    /**
     * Get an instance of the redirector.
     *
     * @return mixed
     */
    function currentRoute()
    {

        return app('request')->getCurrentRoute();

    }
}

if (! function_exists('get_domain_key')) {

    /**
     * Gets a unique instance key determined by domain
     */
    function get_domain_key(): string
    {

        // Now that we know where the instance is bing called from
        // Let's add a domain level cache.

        $host = app('request')->host();

        $url = config('app.url');
        if (is_string($url) && $url !== '') {
            $host = $url;
        }

        $domainKeyParts = config('app.url').config('app.key');
        $slug = \Illuminate\Support\Str::slug($domainKeyParts);
        return md5($slug);

    }

}

if (! function_exists('mix')) {
    /**
     * Get the path to a versioned Mix file. Customized for Leantime.
     *
     * @return Mix|string
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    function mix(string $path = '', string $manifestDirectory = ''): Mix|string
    {
        if (! ($app = app())->bound(Mix::class)) {
            $app->instance(Mix::class, new Mix);
        }

        $mix = $app->make(Mix::class);

        if (empty($path)) {
            return $mix;
        }

        return $mix($path, $manifestDirectory);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (! function_exists('redirect')) {
    /**
     * Get an instance of the redirector.
     *
     * @param string|null $url
     * @param int $http_response_code
     * @param array $headers
     * @param null $secure
     * @return RedirectResponse
     */
    function redirect(string $url = null, int $http_response_code = 302, array $headers = [], $secure = null): RedirectResponse
    {
        return new RedirectResponse(
            trim(preg_replace('/\s\s+/', '', strip_tags($url))),
            $http_response_code
        );
    }
}

if (! function_exists('currentRoute')) {
    /**
     * Get an instance of the redirector.
     *
     * @return mixed
     */
    function currentRoute(): mixed
    {

        return app('request')->getCurrentRoute();

    }
}

if (! function_exists('get_release_version')) {

    /**
     * Gets a unique instance key determined by domain
     */
    function get_release_version()
    {

        $appSettings = app()->make(AppSettings::class);

        return $appSettings->appVersion;

    }

}
