<?php

/**
 * Initialization functions
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Images Module
 * @link http://xaraya.com/index.php/release/152.html
 * @author Images Module Development Team
 */

namespace Xaraya\Modules\Images;

class Version
{
    /**
     * Get module version information
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'name' => 'Images',
            'id' => '152',
            'version' => '2.5.7',
            'displayname' => 'Images',
            'description' => 'Handles image manipulation with resizing/cropping/scaling/rotating and various filters',
            'credits' => 'docs/credits.txt',
            'help' => 'docs/help.txt',
            'changelog' => 'docs/changelog.txt',
            'license' => 'docs/license.txt',
            'official' => 1,
            'author' => 'Carl P. Corliss (carl.corliss@xaraya.com), mikespub, jojodee',
            'contact' => 'http:/xarigami.com/',
            'admin' => 1,
            'class' => 'Utility',
            'category' => 'Global',
            'extensions'
             => [
                 0 => 'gd',
             ],
            'namespace' => 'Xaraya\\Modules\\Images',
            'twigtemplates' => true,
            'dependencyinfo'
             => [
                 0
                  => [
                      'name' => 'Xaraya Core',
                      'version_ge' => '2.4.1',
                  ],
             ],
        ];
    }
}
