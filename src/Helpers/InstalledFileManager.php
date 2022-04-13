<?php

namespace Themearabia\LaravelInstaller\Helpers;

use Illuminate\Support\Facades\File;

class InstalledFileManager
{
    /**
     * Create installed file.
     *
     * @return int
     */
    public function create()
    {
        $installedLogFile = storage_path('installed');
        $message = [
            'project' => config('installer.project.name'),
            'version' => config('installer.project.version')
        ];
        file_put_contents($installedLogFile, maybe_serialize($message));
        return $message;
    }

    /**
     * Update installed file.
     *
     * @return int
     */
    public function update()
    {
        return $this->create();
    }
}
