<?php
namespace Concrete\Package\SecretPreviewUrl\MenuItem\SecretPreviewUrl;

use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfoRepository;

class Controller extends \Concrete\Core\Application\UserInterface\Menu\Item\Controller
{
    public function displayItem()
    {
        $user = $this->app->make(User::class);
        if (!is_object($user) || !$user->isRegistered()) {
            return false;
        }
        $actor = $this->app->make(UserInfoRepository::class)->getByID($user->getUserID());
        if (!is_object($actor) || !$actor->isActive()) {
            return false;
        }

        $c = Page::getCurrentPage();
        if (is_object($c) && !$c->isError() && !$c->isAdminArea() && !$c->isAliasPageOrExternalLink() && !$c->isInTrash() && !$c->isMasterCollection()) {
            $cp = new Checker($c);
            if ($cp->canApprovePageVersions()) {
                /** @var ResolverManagerInterface $resolver */
                $resolver = $this->app->make(ResolverManagerInterface::class);
                $this->menuItem->setLink($resolver->resolve(['/ccm/secret_preview_url', 'dialog', $c->getCollectionID()]));
                return true;
            }
        }

        return false;
    }

}
