<?php
namespace Concrete\Package\SecretUrl\Controller\SinglePage\Dashboard\SecretUrl;

use Concrete\Core\Http\Request;
use Concrete\Core\Search\Pagination\PaginationFactory;
use Concrete\Core\User\UserInfoRepository;
use Concrete\Package\MigrationTool\Page\Controller\DashboardPageController;
use Concrete5cojp\SecretUrl\Search\SignatureList;

class UrlList extends DashboardPageController
{
    public function view()
    {
        $list = $this->app->make(SignatureList::class);
        $factory = new PaginationFactory(Request::getInstance());
        $pagination = $factory->createPaginationObject($list, PaginationFactory::PERMISSIONED_PAGINATION_STYLE_PAGER);
        $this->set('list', $list);
        $this->set('pagination', $pagination);

        $repository = $this->app->make(UserInfoRepository::class);
        $this->set('repository', $repository);

        $dh = $this->app->make('helper/date');
        $this->set('dh', $dh);

        $token = $this->app->make('token');
        $this->set('token', $token);
    }
}