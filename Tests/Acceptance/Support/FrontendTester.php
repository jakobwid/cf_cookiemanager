<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Acceptance\Support;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CodingFreaks\CfCookiemanager\Tests\Acceptance\Support\_generated\FrontendTesterActions;
use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;

/**
 * Default Backend
 */
class FrontendTester extends \Codeception\Actor
{
    use FrontendTesterActions;
    use FrameSteps;
}