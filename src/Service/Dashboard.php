<?php
namespace Concrete5cojp\SecretPreviewUrl\Service;

class Dashboard extends \Concrete\Core\Application\Service\Dashboard
{
    /**
     * {@inheritdoc}
     */
    public function canRead()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canAccessComposer()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function inDashboard($pageOrPath = null)
    {
        return true;
    }
}
