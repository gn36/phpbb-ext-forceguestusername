<?php

/**
*
* @package testing
* @copyright (c) 2016 gn#36
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace gn36\forceguestusername\tests\functional;

/**
* @group functional
*/
class install_test extends \phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('gn36/forceguestusername');
	}

	public function test_validate_posting()
	{
		// Check whether we can still call posting.php in edit mode (even though login prevents any useful use)
		$crawler = self::request('GET', 'posting.php?mode=edit&f=2&p=1');
		$this->assertContains('Username', $crawler->filter('dt')->text());
	}
}
