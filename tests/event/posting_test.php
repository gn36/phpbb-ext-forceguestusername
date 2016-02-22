<?php

/**
 *
* @package testing
* @copyright (c) 2016 gn#36
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace gn36\forceguestusername\tests\event;

class install_test extends \phpbb_test_case
{
	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\config\config */
	protected $config;

	/**
	 * Set up test environment
	 */

	public function test_construct()
	{
		$this->assertInstanceOf('\Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->get_listener());
	}

	public function test_getSubscribedEvents()
	{
		$this->assertEquals(array(
			'core.posting_modify_submission_errors',
		), array_keys(\gn36\forceguestusername\event\posting::getSubscribedEvents()));
	}

	public function test_addlang()
	{
		$listener = $this->get_listener();

		//Should be called when not registered and data incorrect:
		$this->user->expects($this->once())
			->method('add_lang')
			->with($this->stringContains('ucp'));

		$base_event_data = array(
			'post_data' => array(
				'username' => '',
			),
			'error' => array(),
		);
		$event_data = $base_event_data;

		$this->dispatch($listener, $event_data, null);

		// Shouldn't be called when registered:
		$listener = $this->get_listener(2);
		$this->user->expects($this->never())
			->method('add_lang')
			->with($this->stringContains('ucp'));

		$this->dispatch($listener, $event_data, null);

	}

	public function test_check()
	{
		// Test as guest
		//
		$listener = $this->get_listener();

		$base_event_data = array(
			'post_data' => array(
				'username' => '',
			),
			'error' => array(),
		);

		// Empty username
		$event_data = $base_event_data;
		$expected_result_data = $base_event_data;
		$expected_result_data['error'][0] = 'FIELD_TOO_SHORT 3 Username';
		$this->dispatch($listener, $event_data, $expected_result_data);

		// Acceptable username
		$event_data['post_data']['username'] = 'abc';
		$expected_result_data = $event_data;
		$this->dispatch($listener, $event_data, $expected_result_data);

		// Test as registered
		//
		$listener = $this->get_listener(2);

		// Empty is acceptable
		$event_data = $base_event_data;
		$expected_result_data = $base_event_data;
		$this->dispatch($listener, $event_data, $expected_result_data);
	}

	protected function get_listener($user_id = ANONYMOUS)
	{
		$this->user = $this->getMockBuilder('\phpbb\user')
			->disableOriginalConstructor()
			->getMock();

		$this->config = $this->getMock('\phpbb\config\config', null,
			array(array(
				'min_name_chars' => 3
			))
		);

		$lang_map = array(
			array('FIELD_TOO_SHORT', 3, 'Username', 'FIELD_TOO_SHORT 3 Username'),
			array('FIELD_TOO_SHORT', '3', 'Username', 'FIELD_TOO_SHORT 3 Username'),
			array('USERNAME', $this->anything(), $this->anything(), 'Username'),
		);

		$this->user->expects($this->any())
			->method('lang')
			->with($this->anything(), $this->anything(), $this->anything())
			->will($this->returnValueMap($lang_map));

		$this->user->data['user_id'] = $user_id;
		$this->user->data['is_registered'] = $user_id == ANONYMOUS ? false : true;
		$this->user->lang['USERNAME'] = 'Username';

		return new \gn36\forceguestusername\event\posting($this->user, $this->config);
	}

	protected function dispatch($listener, $event_data, $expected_result_data)
	{
		// Create event
		$event = new \Symfony\Component\EventDispatcher\GenericEvent(null, $event_data);

		// Dispatch
		$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
		$dispatcher->addListener('gn36.def_listen', array($listener, 'check_guest_username'));
		$dispatcher->dispatch('gn36.def_listen', $event);

		// Only check result, if data is actually given
		if ($expected_result_data)
		{
			// Modify expected result event to mimic correct dispatch data
			$expected_result = new \Symfony\Component\EventDispatcher\GenericEvent(null, $expected_result_data);
			$expected_result->setDispatcher($dispatcher);
			$expected_result->setName('gn36.def_listen');

			// Check
			$this->assertEquals($expected_result, $event);
		}
	}
}
