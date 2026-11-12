<?php

/**
 * @file
 * @brief       The plugin PenseBete definition
 * @ingroup     PenseBete
 *
 * @defgroup    PenseBete Plugin PenseBete.
 *
 * Keep lists of things to do in the blog administration.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

if (!isset($this) || !is_object($this) || !method_exists($this, 'registerModule') || !isset($this->id) || !is_string($this->id)) {
    return;
}

$this->registerModule(
    'Todo lists',
    'Keep lists of things to do in the blog administration',
    'Jean-Christian Paul Denis and Contributors',
    '0.1',
    [
        'requires' => [
            ['php', '8.3'],
            ['core', '2.40'],
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/src/branch/master/README.md',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2026-11-12T23:44:07+00:00',
    ]
);
