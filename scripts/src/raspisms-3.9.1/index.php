<?php
    require_once(__DIR__ . '/descartes/load.php');

    ############
    # SESSIONS #
    ############
    session_name(SESSION_NAME);
    session_start();

    //Create csrf token if it didn't exist
    if (!isset($_SESSION['csrf']))
    {
        $_SESSION['csrf'] = str_shuffle(uniqid().uniqid());
    }

    //Save previous $_POST for re-populate forms on validation errors
    $_SESSION['previous_http_post'] = $_SESSION['http_post'] ?? [];
    $_SESSION['http_post'] = $_POST;

    //Routing current query
    try 
    {
        descartes\Router::route(ROUTES, $_SERVER['REQUEST_URI']);
    }
    catch (\descartes\exceptions\DescartesException404 $e)
    {
        $controller = new \controllers\internals\HttpError();
        $controller->_404();
    }
    catch (\Throwable $e)
    {
        error_log($e);
        $controller = new \controllers\internals\HttpError();
        $controller->unknown();
    }
