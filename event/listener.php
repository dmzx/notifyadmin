<?php
/**
*
* @package phpBB Extension - Notify Admin on Registration
* @copyright (c) 2015 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\notifyadmin\event;

/**
* Event listener
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var string */
	protected $php_ext;

	/** @var string */
	protected $root_path;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth					$auth
	* @param \phpbb\config\config				$config
	* @param \phpbb\db\driver\driver_interface	$db
	* @param \phpbb\user						$user
	* @param \phpbb\template\template			$template
	* @param \phpbb\request\request			 	$request
	* @param string								$php_ext
	* @param string								$root_path
	*
	*/
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\request\request $request,
		$php_ext,
		$root_path
	)
	{
		$this->auth 		= $auth;
		$this->config 		= $config;
		$this->db 			= $db;
		$this->user 		= $user;
		$this->template 	= $template;
		$this->request 		= $request;
		$this->php_ext 		= $php_ext;
		$this->root_path 	= $root_path;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.ucp_register_user_row_after'		=> 'ucp_register_user_row_after',
		);
	}

	public function ucp_register_user_row_after($event)
	{
		if ($this->config['require_activation'] != USER_ACTIVATION_ADMIN)
		{
			// Grab an array of user_id's with a_user permissions ... these users can activate a user
			$admin_ary = $this->auth->acl_get_list(false, 'a_user', false);
			$admin_ary = (!empty($admin_ary[0]['a_user'])) ? $admin_ary[0]['a_user'] : array();

			// Also include founders
			$where_sql = ' WHERE user_type = ' . USER_FOUNDER;

			if (sizeof($admin_ary))
			{
				 $where_sql .= ' OR ' . $this->db->sql_in_set('user_id', $admin_ary);
			}

			$sql = 'SELECT user_id, username, user_email, user_lang, user_jabber, user_notify_type
				FROM ' . USERS_TABLE . ' ' . $where_sql;
			$result = $this->db->sql_query($sql);

			$data = array(
				'username'			=> $this->request->variable('username', '', true),
				'email'				=> strtolower($this->request->variable('email', '')),
				'user_regdate'		=> time(),
				'user_ip'			=> $this->user->ip,
				'lang'				=> basename($this->request->variable('lang', $this->user->lang_name)),
			);

			while ($row = $this->db->sql_fetchrow($result))
			{
				if (!class_exists('messenger'))
				{
					include($this->root_path . 'includes/functions_messenger.' . $this->php_ext);
				}

				$messenger = new \messenger(false);
				$server_url = generate_board_url();

				$messenger->template('@dmzx_notifyadmin/admin_notify_registered', $data['lang']);
				$messenger->to($row['user_email'], $row['username']);
				$messenger->im($row['user_jabber'], $row['username']);

				$messenger->assign_vars(array(
					'USERNAME'		 	=> htmlspecialchars_decode($data['username']),
					'USER_MAIL'		 	=> $data['email'],
					'USER_REGDATE'		=> date($this->config['default_dateformat'], $data['user_regdate']),
					'USER_IP'		 	=> $data['user_ip'])
				);

				$messenger->send(NOTIFY_EMAIL);
			}
			$this->db->sql_freeresult($result);
		}
	}
}
