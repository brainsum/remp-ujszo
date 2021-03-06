<?php

namespace Crm\UjszoOverridesModule\DI;

use Kdyby\Translation\DI\ITranslationProvider;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;

class UjszoOverridesModuleExtension extends CompilerExtension implements ITranslationProvider
{
    public function loadConfiguration()
    {
        // load services from config and register them to Nette\DI Container
        Compiler::loadDefinitions(
            $this->getContainerBuilder(),
            $this->loadFromFile(__DIR__.'/../config/config.neon')['services']
        );
    }

    /**
     * Return array of directories, that contain resources for translator.
     * @return string[]
     */
    public function getTranslationResources()
    {
        return [__DIR__ . '/../lang/'];
    }
}
