<?php
namespace Concrete\Package\SecretPreviewUrl\Controller\SinglePage\Dashboard\SecretPreviewUrl;

use Concrete\Core\Http\Request;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

class Config extends DashboardPageController
{
    /**
     * @return \Concrete\Core\Config\Repository\Liaison
     */
    protected function getConfigRepository()
    {
        /** @var PackageService $service */
        $service = $this->app->make(PackageService::class);
        $package = $service->getClass('secret_preview_url');
        return $package->getFileConfig();
    }

    public function view()
    {
        $lifetime = $this->getConfigRepository()->get('url.lifetime', 10080); // 1 week
        $this->set('lifetime', $lifetime);
    }

    public function updated()
    {
        $this->set('message', t("Settings saved."));
        $this->view();
    }

    public function save_settings()
    {
        if ($this->token->validate("save_settings")) {
            if (Request::isPost()) {
                $lifetime = (int) $this->post('lifetime');
                if ($lifetime) {
                    $this->getConfigRepository()->save('url.lifetime', $lifetime);
                }
                /** @var ResolverManagerInterface $resolver */
                $resolver = $this->app->make(ResolverManagerInterface::class);
                return new RedirectResponse((string) $resolver->resolve(['/dashboard/secret_preview_url/config', 'updated']));
            }
        } else {
            $this->set('error', array($this->token->getErrorMessage()));
        }
    }
}