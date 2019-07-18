<?php

defined('C5_EXECUTE') or die("Access Denied.");
/**
 * @var $router \Concrete\Core\Routing\Router
 * Base path: /ccm/secret_url
 * Namespace: Concrete\Package\SecretUrl\Controller\
 */
$router->get('/dialog/{cID}', 'SecretUrl::dialog');
$router->post('/dialog/{cID}/add', 'SecretUrl::add');
$router->post('/dialog/delete/{signature}', 'SecretUrl::delete');
$router->get('/view/{signature}', 'SecretUrl::preview_page');