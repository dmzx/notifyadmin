<?php
/**
 *
 * @package phpBB Extension - Notify Admin on Registration
 * @copyright (c) 2021 dmzx - https://www.dmzx-web.net
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace dmzx\notifyadmin\migrations;

use phpbb\db\migration\migration;

class notifyadmin_install extends migration
{
	static public function depends_on()
	{
		return [
				'\phpbb\db\migration\data\v330\v330'
		];
	}

	public function update_data()
	{
		return [
			// Add configs
			['config.add', ['notifyadmin_version', '1.0.4']],
		];
	}
}
