<?php
namespace Concrete\Package\SecretPreviewUrl\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

class SecretPreviewUrl extends DashboardPageController
{
    public function view()
    {
        /** @var ResolverManagerInterface $resolver */
        $resolver = $this->app->make(ResolverManagerInterface::class);

        return new RedirectResponse((string) $resolver->resolve(['/dashboard/secret_preview_url/url_list']));
    }
}