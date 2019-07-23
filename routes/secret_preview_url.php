<?php

defined('C5_EXECUTE') or die('Access Denied.');
/**
 * @var $router \Concrete\Core\Routing\Router
 * Base path: /ccm/secret_preview_url
 * Namespace: Concrete\Package\SecretPreviewUrl\Controller\
 */
$router->get('/dialog/{cID}', 'SecretPreviewUrl::dialog');
$router->post('/dialog/{cID}/add', 'SecretPreviewUrl::add');
$router->post('/dialog/delete/{signature}', 'SecretPreviewUrl::delete');
$router->get('/view/{signature}', 'SecretPreviewUrl::preview_page');
