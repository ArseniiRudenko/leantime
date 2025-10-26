<?php

namespace Leantime\Core\UI;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Leantime\Core\Configuration\AppSettings;
use Leantime\Core\Configuration\Environment;
use Leantime\Core\Events\DispatchesEvents;
use Leantime\Core\Events\EventDispatcher;
use Leantime\Core\Files\FileManager;
use Leantime\Core\Language;
use Leantime\Domain\Auth\Services\Auth;
use Leantime\Domain\Setting\Repositories\Setting;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * theme - Engine for handling themes
 */
class Theme
{
    use DispatchesEvents;

    /**
     * Name of default theme
     *
     * @var string
     *
     * @static
     *
     * @final
     */
    public const DEFAULT = 'default';

    /**
     * Theme configuration file (excluding .ini extension)
     *
     * @var string
     *
     * @static
     *
     * @final
     */
    public const DEFAULT_INI = 'theme';

    /**
     * Theme style file (excluding .css extension)
     *
     * @var string
     *
     * @static
     *
     * @final
     */
    public const DEFAULT_CSS = 'light-leantime';

    /**
     * Theme JavaScript library (excluding .js extension)
     *
     * @var string
     *
     * @static
     *
     * @final
     */
    public const DEFAULT_JS = 'theme';

    /**
     * Theme default logo
     *
     * @var string
     *
     * @static
     *
     * @final
     */
    public const DEFAULT_LOGO = '/dist/images/logo.svg';

    /**
     * Theme style customization file (excluding .css extension)
     *
     * @var string
     *
     * @static
     *
     * @final
     */
    public const CUSTOM_CSS = 'custom';

    /**
     * Theme JavaScript customization file (excluding .js extension)
     *
     * @var string
     *
     * @static
     *
     * @final
     */
    public const CUSTOM_JS = 'custom';

    private Environment $config;

    private Setting $settingsRepo;

    private Language $language;

    /**
     * @var language
     */
    private AppSettings $appSettings;

    private FileManager $fileManager;

    private array|false $iniData;

    private array $backgroundTypes = ['gradient', 'image'];

    private array $backgroundSources = ['unsplash', 'upload'];

    /**
     * possible font choices
     */
    public array $fonts = [
        'roboto' => 'Roboto',
        'atkinson' => 'Atkinson Hyperlegible',
        'shantell' => 'Shantell Sans',
    ];

    /**
     * possible font choices
     */
    public array $fontTooltips = [
        'roboto' => 'Designed to be easy to read on a variety of devices.',
        'atkinson' => 'Atkinson was specifically developed for readers with low vision.',
        'shantell' => 'The shape of the letters and increased spacing makes words less crowded and easier to read.',
    ];

    /**
     * __construct - Constructor
     */
    public function __construct(
        Environment $config,
        Setting $settingsRepo,
        Language $language,
        AppSettings $appSettings,
        FileManager $fileManager
    ) {
        $this->config = $config;
        $this->settingsRepo = $settingsRepo;
        $this->iniData = [];
        $this->language = $language;
        $this->appSettings = $appSettings;
        $this->fileManager = $fileManager;

    }

    public function getAvailableFonts()
    {
        return self::dispatchFilter('fonts', $this->fonts);
    }

    public function getBackgroundImage(): ?string
    {
        if (Auth::isLoggedIn()) {
            return $this->settingsRepo->getSetting('usersettings.'.session('userdata.id').'.backgroundImage');
        }

        return null;
    }

    public function setBackgroundImage(string $url): void
    {
        if (Auth::isLoggedIn()) {
            $this->settingsRepo->saveSetting('usersettings.'.session('userdata.id').'.backgroundType', 'image');
            $this->settingsRepo->saveSetting('usersettings.'.session('userdata.id').'.backgroundImage', $url);
        }
    }

    public function getBackgroundType(): string
    {
        if (Auth::isLoggedIn()) {
            return $this->settingsRepo->getSetting('usersettings.'.session('userdata.id').'.backgroundType') ?? 'gradient';
        }

        return 'gradient';
    }

    public function setBackgroundType(string $type): void
    {
        if (Auth::isLoggedIn()) {
            $this->settingsRepo->saveSetting('usersettings.'.session('userdata.id').'.backgroundType', $type);
            if ($type == 'gradient') {
                $this->settingsRepo->deleteSetting('usersettings.'.session('userdata.id').'.backgroundImage');
            }

        }
    }

    /**
     * getActive - Return active theme id
     *
     * @return string Active theme identifier
     */
    public function getActive(): string
    {

        // Reset .ini data
        $this->iniData = [];

        if (session()->exists('usersettings.theme') && Auth::isLoggedIn()) {
            return session('usersettings.theme');
        }

        // Return user specific theme, if active
        // This is an active logged in session.
        if (Auth::isLoggedIn()) {
            // User is logged in, we don't have a theme yet, check settings
            $theme = $this->settingsRepo->getSetting('usersettings.'.session('userdata.id').'.theme');
            if ($theme !== false) {
                $this->setActive($theme);

                return $theme;
            }
        }

        // No generic theme set. Check if cookie is set
        if (isset($_COOKIE['theme'])) {
            $this->setActive($_COOKIE['theme']);

            return $_COOKIE['theme'];
        }

        // Return configured
        // Nothing set, get default theme from config
        if (isset($this->config->defaultTheme) && ! empty($this->config->defaultTheme)) {
            $this->setActive($this->config->defaultTheme);

            return $this->config->defaultTheme;
        }

        // Return default
        return static::DEFAULT;
    }

    /**
     * getColorMode - Return active color mode
     *
     * @return string Active theme identifier
     */
    public function getColorMode()
    {

        // Return generic theme
        if (session()->exists('usersettings.colorMode') && Auth::isLoggedIn()) {
            return session('usersettings.colorMode');
        }

        if (Auth::isLoggedIn()) {
            // User is logged in, we don't have a theme yet, check settings
            $colorMode = $this->settingsRepo->getSetting('usersettings.'.session('userdata.id').'.colorMode');
            if ($colorMode !== false) {
                $this->setColorMode($colorMode);

                return $colorMode;
            }
        }

        // No generic theme set. Check if cookie is set
        if (isset($_COOKIE['colorMode'])) {
            $this->setColorMode($_COOKIE['colorMode']);

            return $_COOKIE['colorMode'];
        }

        // Return default
        session(['usersettings.colorMode' => 'light-leantime']);

        return 'light-leantime';
    }

    /**
     * getFont - Return active font
     *
     * @return string Active theme identifier
     */
    public function getFont()
    {

        // Return generic theme
        if (session()->exists('usersettings.themeFont') && Auth::isLoggedIn()) {
            $this->setFont(session('usersettings.themeFont'));

            return session('usersettings.themeFont');
        }

        if (Auth::isLoggedIn()) {

            // User is logged in, we don't have a theme yet, check settings
            $themeFont = $this->settingsRepo->getSetting('usersettings.'.session('userdata.id').'.themeFont');
            if ($themeFont !== false) {
                $this->setFont($themeFont);

                return $themeFont;
            }
        }

        if (isset($_COOKIE['themeFont'])) {
            $this->setFont($_COOKIE['themeFont']);

            return $_COOKIE['themeFont'];
        }

        // Return default
        $this->setFont('roboto');

        return 'roboto';
    }

    /**
     * setActive - Set active theme
     *
     * Note: After setActive, the language settings need to be reloaded/reset, because languages are theme specific
     *
     * @param  string  $id  Active theme identifier.
     *
     * @throws Exception Exception if theme does not exist.
     */
    public function setActive(string $id): void
    {

        if ($id == '') {
            $id = static::DEFAULT;
        }

        // not a valid theme. Use default
        if (! is_dir(ROOT.'/theme/'.$id) || ! file_exists(ROOT.'/theme/'.$id.'/'.static::DEFAULT_INI.'.ini')) {
            $id = static::DEFAULT;
        }

        // Only set if user is logged in
        if (Auth::isLoggedIn()) {
            session(['usersettings.theme' => $id]);
        }

        EventDispatcher::addFilterListener(
            'leantime.core.http.httpkernel.handle.beforeSendResponse',
            fn ($response) => tap($response, fn (Response $response) => $response->headers->setCookie(
                Cookie::create('theme')
                    ->withValue($id)
                    ->withExpires(time() + 60 * 60 * 24 * 30)
                    ->withPath(Str::finish($this->config->appDir, '/'))
                    ->withSameSite('Strict')
            ))
        );
    }

    /**
     * setColorModel - Set active theme
     *
     *
     * @param  string  $colorMode  color mode of theme (light, dark).
     */
    public function setColorMode(string $colorMode): void
    {
        if ($colorMode == '') {
            $colorMode = 'light-leantime';
        }

        // Only store colors in session for logged in users
        if (Auth::isLoggedIn()) {
            session(['usersettings.colorMode' => $colorMode]);
        }

        EventDispatcher::addFilterListener(
            'leantime.core.http.httpkernel.handle.beforeSendResponse',
            fn ($response) => tap($response, fn (Response $response) => $response->headers->setCookie(
                Cookie::create('colorMode')
                    ->withValue($colorMode)
                    ->withExpires(time() + 60 * 60 * 24 * 30)
                    ->withPath(Str::finish($this->config->appDir, '/'))
                    ->withSameSite('Strict')
            ))
        );
    }

    /**
     * setFont - Set active font
     *
     *
     * @param  string  $font  font name key (roboto, atkinson).
     */
    public function setFont(string $font): void
    {

        if ($font == '') {
            $font = 'roboto';
        }

        if (Auth::isLoggedIn()) {
            session(['usersettings.themeFont' => $font]);
        }

        EventDispatcher::addFilterListener(
            'leantime.core.http.httpkernel.handle.beforeSendResponse',
            fn ($response) => tap($response, fn (Response $response) => $response->headers->setCookie(
                Cookie::create('themeFont')
                    ->withValue($font)
                    ->withExpires(time() + 60 * 60 * 24 * 30)
                    ->withPath(Str::finish($this->config->appDir, '/'))
                    ->withSameSite('Strict')
            ))
        );
    }


    /**
     * getAll - Return an array of all themes
     *
     * @return array return an array of all themes
     *
     * @throws BindingResolutionException
     */
    public function getAll(): array
    {
        $theme = $this->getActive();

        $themes = [];

        $handle = opendir(ROOT.'/theme');
        if ($handle === false) {
            return $themes;
        }

        while (false !== ($themeDir = readdir($handle))) {
            if ($themeDir == '.' || $themeDir == '..') {
                continue;
            }

            // Ready theme ini
            $themeIni = ROOT
                .'/theme/'
                .$themeDir
                .'/theme.ini';

            if (file_exists($themeIni)) {
                $iniData = parse_ini_file(
                    $themeIni,
                    true,
                    INI_SCANNER_RAW
                );

                if (isset($iniData['general']['name']) && $iniData['general']['name'] !== null) {
                    $themes[$themeDir] = $iniData['general'];
                }
            }
        }

        return $themes;
    }

    /**
     * getDir - Return the root directory of the currently active theme
     *
     * @return string Root directory of currently active theme
     */
    public function getDir(): string
    {
        return ROOT.'/theme/'.$this->getActive();
    }

    /**
     * getDir - Return the root directory of the default theme
     *
     * @return string Root directory of default theme
     */
    public function getDefaultDir(): string
    {

        return ROOT.'/theme/'.static::DEFAULT;
    }

    /**
     * getUrl() - Return an URL pointing to the root directory of the currently active theme
     *
     * @return string Root URL currently active theme
     */
    public function getUrl(): string
    {

        return $this->config->appUrl.'/theme/'.$this->getActive();
    }

    /**
     * getDefaultUrl() - Return an URL pointing to the root directory of the default theme
     *
     * @return string Root URL default theme
     */
    public function getDefaultUrl(): string
    {

        return ROOT.'/theme/'.static::DEFAULT;
    }

    /**
     * getStyleUrl - Return URL that allows loading the style file of the theme
     *
     * @return string|false URL to the css style file of the current theme or false, if it does not exist
     */
    public function getStyleUrl(): string|false
    {
        return $this->getAssetPath($this->getColorMode(), 'css');
    }

    /**
     * getCustomStyleUrl - Return URL that allows loading the customized part of the style file of the theme
     *
     * @return string|false URL to the customized part of the css style file of the current theme or false, if it does not exist
     */
    public function getCustomStyleUrl(): string|false
    {
        return $this->getAssetPath(static::CUSTOM_CSS, 'css');
    }

    /**
     * getJsUrl - Return URL that allows loading the JavaScript file of the theme
     *
     * @return string|false URL to the JavaScript file of the current theme or false, if it does not exist
     */
    public function getJsUrl(): string|false
    {
        return $this->getAssetPath(static::DEFAULT_JS, 'js');
    }

    /**
     * getCustomJsUrl - Return URL that allows loading the customized part of the JavaScript file of the theme
     *
     * @return string|false URL to the customized part of the JavaScript file of the current theme or false, if it does not exist
     */
    public function getCustomJsUrl(): string|false
    {
        return $this->getAssetPath(static::CUSTOM_JS, 'js');
    }

    /**
     * getAssetPath - Get localized name of theme
     *
     * @param  string  $fileName  Filename of asset without extension.
     * @param  string  $assetType  Asset type either js or css.
     * @return string|bool returns file path to asset. false if file does not exist
     */
    private function getAssetPath(string $fileName, string $assetType): string|bool
    {
        if ($fileName == '' || ($assetType != 'css' && $assetType != 'js')) {
            return false;
        }

        if (file_exists($this->getDir().'/'.$assetType.'/'.$fileName.'.min.'.$assetType)) {
            return $this->getUrl().'/'.$assetType.'/'.$fileName.'.min.'.$assetType.'?v='.$this->appSettings->appVersion;
        }

        if (file_exists($this->getDir().'/'.$assetType.'/'.$fileName.'.'.$assetType)) {
            return $this->getUrl().'/'.$assetType.'/'.$fileName.'.'.$assetType.'?v='.$this->appSettings->appVersion;
        }

        return false;
    }

    /**
     * Retrieves the name of the theme.
     *
     * First, it checks if the INI data is empty. If it is, the method tries to read the INI data.
     * If an exception occurs during the reading process, it is logged in the error log and the method returns
     * the language translation of the active theme name using the "__" method of the $language object.
     *
     * If the INI data contains a 'name' key, it returns the corresponding value.
     *
     * If none of the above conditions are met, it returns the language translation of the active theme name
     * using the "__" method of the $language object.
     *
     * @return string The name of the theme.
     */
    public function getName(): string
    {

        if (empty($this->iniData)) {
            try {
                $this->readIniData();
            } catch (Exception $e) {
                report($e);

                return $this->language->__('theme.'.$this->getActive().'name');
            }
        }

        if (isset($this->iniData['name'])) {
            return $this->iniData['name'];
        }

        return $this->language->__('theme.'.$this->getActive().'name');
    }

    /**
     * Retrieves the version number from the initialization data or returns an empty string if not available.
     *
     * @return string The version number.
     */
    public function getVersion(): string
    {

        if (empty($this->iniData)) {
            try {
                $this->readIniData();
            } catch (Exception $e) {
                report($e);

                return '';
            }
        }

        if (isset($this->iniData['general']['version'])) {
            return $this->iniData['general']['version'];
        }

        return '';
    }

    /**
     * Retrieves the URL of the company logo from the user's settings or the default logo path.
     *
     * @return string|false The URL of the company logo, or false if the company doesn't have a logo.
     */
    public function getLogoUrl(): string|false
    {

        // Session Logo Path needs to be set here
        // Logo will be in there. Session will be renewed when new logo is updated or theme is changed

        $logoPath = false;
        if (session()->exists('companysettings.logoPath') === false
            || session('companysettings.logoPath') == '') {

            $logoPath = $this->settingsRepo->getSetting('companysettings.logoPath');

            if ($logoPath === false) {
                session(['companysettings.logoPath' => false]);

                return false;
            }

            // File comes from config
            if (str_starts_with($logoPath, 'http')) {
                session(['companysettings.logoPath' => $logoPath]);

                return session('companysettings.logoPath');
            }

            // File was uploaded. Check if we can find it
            $fileUrl = $this->fileManager->getFileUrl($logoPath, 'public', (60 * 24));
            if ($fileUrl) {
                session(['companysettings.logoPath' => $fileUrl]);

                return session('companysettings.logoPath');
            }

            // If we can't find a logo in the db, the company doesn't have a logo. Stop trying
            session(['companysettings.logoPath' => false]);

        }

        return session('companysettings.logoPath');

    }


    /**
     * readIniData - Read theme.ini configuration data
     *
     * @throws Exception
     */
    private function readIniData(): void
    {
        if (! file_exists(ROOT.'/theme/'.$this->getActive().'/'.static::DEFAULT_INI.'.ini')) {
            report('Configuration file for theme '.$this->getActive().' not found');
            $this->clearCache();
            $this->setActive('default');
        }
        $this->iniData = parse_ini_file(
            ROOT.'/theme/'.$this->getActive().'/'.static::DEFAULT_INI.'.ini',
            true,
            INI_SCANNER_TYPED
        );
        if ($this->iniData === false) {
            $this->iniData = [];
        }
    }

    public static function clearCache(): void
    {
        session()->forget('usersettings.colorMode');
        session()->forget('usersettings.colorScheme');
        session()->forget('usersettings.themeFont');
        session()->forget('usersettings.theme');
    }
}
