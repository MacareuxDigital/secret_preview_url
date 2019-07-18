<?php
namespace Concrete5cojp\SecretUrl\Service;

class Dashboard extends \Concrete\Core\Application\Service\Dashboard
{
    /**
     * @inheritDoc
     */
    public function canRead()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canAccessComposer()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function inDashboard($pageOrPath = null)
    {
        return true;
    }

}