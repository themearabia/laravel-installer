<?php

namespace Themearabia\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use Themearabia\LaravelInstaller\Events\LaravelInstallerFinished;
use Themearabia\LaravelInstaller\Helpers\EnvironmentManager;
use Themearabia\LaravelInstaller\Helpers\FinalInstallManager;
use Themearabia\LaravelInstaller\Helpers\InstalledFileManager;

class FinalController extends Controller
{
    /**
     * Update installed file and display finished view.
     *
     * @param \Themearabia\LaravelInstaller\Helpers\InstalledFileManager $fileManager
     * @param \Themearabia\LaravelInstaller\Helpers\FinalInstallManager $finalInstall
     * @param \Themearabia\LaravelInstaller\Helpers\EnvironmentManager $environment
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function finish(InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment)
    {
        $finalMessages = $finalInstall->runFinal();
        $finalStatusMessage = $fileManager->update();
        $finalEnvFile = $environment->getEnvContent();

        event(new LaravelInstallerFinished);

        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
