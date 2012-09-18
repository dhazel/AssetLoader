<?php
namespace AssetLoader;

use Zend\ModuleManager\ModuleManager,
    \finfo;

/**
 * Module for loading assets in development.
 */
class Module
{
    /**
     * Collected asset paths.
     *
     * @var array
     */
    protected $assetPaths = array();

    /**
     * Initialize the module.
     *
     * @param  ModuleManager $moduleManager
     * @return void
     */
    public function init(ModuleManager $moduleManager)
    {
        $moduleManager->getEventManager()->attach(
            'loadModule',
            array($this, 'addAssetPath')
        );
    }

    public function onBootstrap($e) 
    {
        $this->checkRequestUriForAsset($e);
    }

    /**
     * Add an asset path from a module.
     *
     * @param  Zend\EventManager\Event $event
     * @return void
     */
    public function addAssetPath($event)
    {
        $module = $event->getModule();

        if (!method_exists($module, 'getAssetPath')) {
            return;
        }

        if (null !== ($assetPath = $module->getAssetPath())) {
            $this->assetPaths[] = rtrim($assetPath, '\\/');
        }
    }

    /**
     * Check a request for a valid file asset.
     *
     * @param  Zend\EventManager\Event $event
     * @return void
     */
    public function checkRequestUriForAsset($event)
    {
        $request = $event->getRequest();

        if (!method_exists($request, 'getUri')) {
            return;
        }

        if (method_exists($request, 'getBaseUrl')) {
            $baseUrlLength = strlen($request->getBaseUrl() ?: '');
        } else {
            $baseUrlLength = 0;
        }

        $path = substr($request->getUri()->getPath(), $baseUrlLength);

        foreach ($this->assetPaths as $assetPath) {
            if (file_exists($assetPath . $path)) {
                $this->sendFile($assetPath . $path);
            }
        }
    }

    /**
     * Send an asset file.
     *
     * @param  string $file
     * @return void
     */
    protected function sendFile($filename)
    {
        $finfo    = new finfo(FILEINFO_MIME);
        $mimeType = $finfo->file($filename);

        header('Content-Type: ' . $mimeType);
        readfile($filename);
        exit;
    }
}
