<?php
/**
 *
 * @package forceguestusername
 * @copyright (c) 2016 gn#36
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace gn36\forceguestusername\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class posting implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.posting_modify_submission_errors'	=> 'check_guest_username',
		);
	}

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\config\config */
	protected $config;

	public function __construct(\phpbb\user $user, \phpbb\config\config $config)
	{
		$this->user = $user;
		$this->config = $config;
	}

	public function check_guest_username($event)
	{
		$post_data = $event['post_data'];

		if (!$this->user->data['is_registered'])
		{
			if (empty($post_data['username']))
			{
				//Username too short 
				$this->user->add_lang('ucp');
				$error = $event['error'];
				$error[] = $this->user->lang('FIELD_TOO_SHORT', $this->config['min_name_chars'], $this->user->lang['USERNAME']);
				$event['error'] = $error;
			}
		}
	}
}
