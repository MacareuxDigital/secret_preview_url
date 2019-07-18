<?php

namespace Concrete\Package\SecretUrl;

use Concrete\Core\Application\Service\UserInterface\Menu;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Page;
use Concrete\Core\Routing\Router;
use Concrete\Core\Routing\RouterInterface;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

class Controller extends Package
{
    protected $appVersionRequired = '8.5.0';
    protected $pkgHandle = 'secret_url';
    protected $pkgVersion = '0.0.1';
    protected $pkgAutoloaderRegistries = [
        'src' => '\Concrete5cojp\SecretUrl',
    ];

    public function getPackageName()
    {
        return t('Preview URL');
    }

    public function getPackageDescription()
    {
        return t('Get the link for sharing unpublished pages.');
    }

    public function on_start()
    {
        /** @var Router $router */
        $router = $this->app->make(RouterInterface::class);
        $router->buildGroup()
            ->setPrefix('/ccm/secret_url')
            ->setNamespace('Concrete\Package\SecretUrl\Controller')
            ->routes('secret_url.php', $this->getPackageHandle());

        /** @var Menu $menu */
        $menu = $this->app->make('helper/concrete/ui/menu');
        $menu->addPageHeaderMenuItem('secret_url', $this->getPackageHandle(), [
            'icon' => 'user-secret',
            'label' => t('Preview URL'),
            'position' => 'left',
            'linkAttributes' => [
                'class' => 'dialog-launch',
                'dialog-width' => 620,
                'dialog-height' => 480,
                'dialog-modal' => true,
                'dialog-title' => t('Preview URL'),
            ]
        ]);
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installContentFile('config/singlepages.xml');
    }
}