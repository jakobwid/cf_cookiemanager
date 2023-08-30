<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Acceptance\Frontend;

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

use CodingFreaks\CfCookiemanager\Tests\Acceptance\Support\FrontendTester;

/**
 * Tests the styleguide backend module can be loaded
 */
class ConsentModalTestCest
{

    const PATH_root = '/';

    const SELECTOR_consent_modal = '#cm';
    const SELECTOR_consent_modal_save_all = '#c-p-bn'; //primary button
    const COOKIE_NAME = 'cf_cookie';

    /**
     * @param FrontendTester $I
     */
    public function _before(FrontendTester $I)
    {

    }


    /**
     * @param FrontendTester $I
     */
    public function acceptAll(FrontendTester $I): void
    {
        //$I->amOnPage("/");
     //   $I->waitForJS('return typeof cf_cookieconfig === "object"', 10);
       // $I->waitForElementVisible(self::SELECTOR_consent_modal);
       // $I->clickWithLeftButton(['id' => self::SELECTOR_consent_modal_save_all]);
       // $I->waitForElementNotVisible(self::SELECTOR_consent_modal);
      //  $I->seeCookie(self::COOKIE_NAME);
        $I->amOnPage('/');
        $I->assertEquals(
            "lol",
            $I->grabCookie(self::COOKIE_NAME, ['path' => self::PATH_root])
        );
    }

}