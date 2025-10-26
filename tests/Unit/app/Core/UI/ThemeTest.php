<?php

namespace Unit\app\Core\UI;

use Leantime\Core\Configuration\AppSettings;
use Leantime\Core\Configuration\Environment;
use Leantime\Core\Files\FileManager;
use Leantime\Core\Language;
use Leantime\Core\UI\Theme;
use Leantime\Domain\Setting\Repositories\Setting;

class ThemeTest extends \Unit\TestCase
{
    use \Codeception\Test\Feature\Stub;

    /**
     * The test object
     *
     * @var Theme
     */
    protected $theme;

    protected $settingsRepoMock;

    protected $languageMock;

    protected $configMock;

    protected $appSettingsMock;

    protected $fileManagerMock;

    protected function setUp(): void
    {

        parent::setUp();

        if (! defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost');
        }

        $this->settingsRepoMock = $this->make(Setting::class, [

        ]);
        $this->languageMock = $this->make(Language::class, [

        ]);

        $this->fileManagerMock = $this->make(FileManager::class, [

        ]);

        $this->configMock = $this->make(Environment::class, [
            'primarycolor' => '#123',
            'secondarycolor' => '#123',

        ]);

        $this->appSettingsMock = $this->make(AppSettings::class, [
            'appVersion' => '123',
        ]);

    }

    protected function _after()
    {
        $this->theme = null;
    }

    // Write tests below



}
