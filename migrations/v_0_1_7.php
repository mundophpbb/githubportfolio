<?php
/**
 *
 * GitHub Portfolio. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 MundoPHPBB
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mundophpbb\githubportfolio\migrations;

class v_0_1_7 extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\\mundophpbb\\githubportfolio\\migrations\\v_0_1_6'];
    }

    public function update_data()
    {
        return [
            ['config.add', ['mundophpbb_githubportfolio_last_sync_time', '0']],
            ['config.add', ['mundophpbb_githubportfolio_last_sync_count', '0']],
        ];
    }

    public function revert_data()
    {
        return [
            ['config.remove', ['mundophpbb_githubportfolio_last_sync_time']],
            ['config.remove', ['mundophpbb_githubportfolio_last_sync_count']],
        ];
    }
}
