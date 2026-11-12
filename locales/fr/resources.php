<?php

/**
 * @file
 * @brief       The plugin PenseBete help resource
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

\Dotclear\App::backend()->resources()->set('help', 'PenseBete', __DIR__ . '/help/help.html');
