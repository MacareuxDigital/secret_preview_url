<?php
namespace Concrete\Package\SecretUrl\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

class SecretUrl extends DashboardPageController
{
    public function view()
    {
        /** @var ResolverManagerInterface $resolver */
        $resolver = $this->app->make(ResolverManagerInterface::class);

        return new RedirectResponse((string) $resolver->resolve(['/dashboard/secret_url/url_list']));
    }
}