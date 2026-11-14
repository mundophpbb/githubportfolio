<?php
/**
 *
 * GitHub Portfolio. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 MundoPHPBB
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mundophpbb\githubportfolio\acp;

use mundophpbb\githubportfolio\service\github_api;

class main_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $config, $request, $template, $user, $phpbb_container;

        $this->tpl_name = 'acp_githubportfolio_body';
        $this->page_title = $user->lang('ACP_GITHUBPORTFOLIO_TITLE');

        add_form_key('mundophpbb_githubportfolio');

        /** @var github_api $github_api */
        $github_api = $phpbb_container->get('mundophpbb.githubportfolio.github_api');

        $is_submit = $request->is_set_post('submit');
        $is_refresh_cache = $request->is_set_post('refresh_cache');

        if ($is_submit || $is_refresh_cache)
        {
            if (!check_form_key('mundophpbb_githubportfolio'))
            {
                trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action));
            }

            $sort_by = $request->variable('mundophpbb_githubportfolio_sort_by', 'updated', true);
            if (!in_array($sort_by, ['updated', 'stars', 'name'], true))
            {
                $sort_by = 'updated';
            }

            set_config('mundophpbb_githubportfolio_enabled', $request->variable('mundophpbb_githubportfolio_enabled', 0));
            set_config('mundophpbb_githubportfolio_username', $request->variable('mundophpbb_githubportfolio_username', '', true));
            set_config('mundophpbb_githubportfolio_token', $request->variable('mundophpbb_githubportfolio_token', '', true));
            set_config('mundophpbb_githubportfolio_limit', max(1, min(50, $request->variable('mundophpbb_githubportfolio_limit', 6))));
            set_config('mundophpbb_githubportfolio_cache_ttl', max(60, $request->variable('mundophpbb_githubportfolio_cache_ttl', 1800)));
            set_config('mundophpbb_githubportfolio_hide_forks', $request->variable('mundophpbb_githubportfolio_hide_forks', 1));
            set_config('mundophpbb_githubportfolio_hide_archived', $request->variable('mundophpbb_githubportfolio_hide_archived', 1));
            set_config('mundophpbb_githubportfolio_sort_by', $sort_by);
            set_config('mundophpbb_githubportfolio_manual_order', trim($request->variable('mundophpbb_githubportfolio_manual_order', '', true)));
            set_config('mundophpbb_githubportfolio_selected_repos', trim($request->variable('mundophpbb_githubportfolio_selected_repos', '', true)));
            set_config('mundophpbb_githubportfolio_hidden_repos', trim($request->variable('mundophpbb_githubportfolio_hidden_repos', '', true)));
            set_config('mundophpbb_githubportfolio_show_portal_block', $request->variable('mundophpbb_githubportfolio_show_portal_block', 1));
            set_config('mundophpbb_githubportfolio_portal_title', $request->variable('mundophpbb_githubportfolio_portal_title', '', true));
            set_config('mundophpbb_githubportfolio_portal_limit', max(1, min(12, $request->variable('mundophpbb_githubportfolio_portal_limit', 4))));
            set_config('mundophpbb_githubportfolio_featured_repos', trim($request->variable('mundophpbb_githubportfolio_featured_repos', '', true)));
            set_config('mundophpbb_githubportfolio_featured_title', $request->variable('mundophpbb_githubportfolio_featured_title', '', true));
            set_config('mundophpbb_githubportfolio_repo_discussions', trim($request->variable('mundophpbb_githubportfolio_repo_discussions', '', true)));

            if ($is_refresh_cache)
            {
                set_config('mundophpbb_githubportfolio_cache_buster', (string) time());
                $github_api->get_all_repositories();
                trigger_error($user->lang('ACP_GITHUBPORTFOLIO_CACHE_REFRESHED') . adm_back_link($this->u_action));
            }

            trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
        }

        $current_sort = isset($config['mundophpbb_githubportfolio_sort_by']) ? (string) $config['mundophpbb_githubportfolio_sort_by'] : 'updated';
        if (!in_array($current_sort, ['updated', 'stars', 'name'], true))
        {
            $current_sort = 'updated';
        }

        $all_repos = $github_api->get_all_repositories();
        $featured_repos = $github_api->get_featured_repositories($all_repos);
        $regular_repos = $github_api->get_regular_repositories($all_repos);
        $discussion_total = 0;
        $languages = [];

        foreach ($all_repos as $repo)
        {
            if (!empty($repo['discussion_url']))
            {
                $discussion_total++;
            }

            $language = trim((string) ($repo['language'] ?? ''));
            if ($language !== '')
            {
                $languages[strtolower($language)] = $language;
            }
        }

        $username = trim((string) ($config['mundophpbb_githubportfolio_username'] ?? ''));
        $last_sync_timestamp = !empty($config['mundophpbb_githubportfolio_last_sync_time'])
            ? (int) $config['mundophpbb_githubportfolio_last_sync_time']
            : 0;
        $last_sync_count = isset($config['mundophpbb_githubportfolio_last_sync_count'])
            ? (int) $config['mundophpbb_githubportfolio_last_sync_count']
            : 0;
        $show_portal_block = isset($config['mundophpbb_githubportfolio_show_portal_block'])
            ? (int) $config['mundophpbb_githubportfolio_show_portal_block']
            : 1;

        $template->assign_vars([
            'U_ACTION'                                     => $this->u_action,
            'U_GITHUBPORTFOLIO_PAGE'                       => append_sid('app.php/githubportfolio'),
            'GITHUBPORTFOLIO_PROFILE_URL'                  => $username !== '' ? 'https://github.com/' . rawurlencode($username) : '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_ENABLED'           => (int) ($config['mundophpbb_githubportfolio_enabled'] ?? 0),
            'MUNDOPHPBB_GITHUBPORTFOLIO_USERNAME'          => $config['mundophpbb_githubportfolio_username'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_TOKEN'             => $config['mundophpbb_githubportfolio_token'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_LIMIT'             => (int) ($config['mundophpbb_githubportfolio_limit'] ?? 6),
            'MUNDOPHPBB_GITHUBPORTFOLIO_CACHE_TTL'         => (int) ($config['mundophpbb_githubportfolio_cache_ttl'] ?? 1800),
            'MUNDOPHPBB_GITHUBPORTFOLIO_HIDE_FORKS'        => (int) ($config['mundophpbb_githubportfolio_hide_forks'] ?? 1),
            'MUNDOPHPBB_GITHUBPORTFOLIO_HIDE_ARCHIVED'     => (int) ($config['mundophpbb_githubportfolio_hide_archived'] ?? 1),
            'MUNDOPHPBB_GITHUBPORTFOLIO_SORT_BY'           => $current_sort,
            'MUNDOPHPBB_GITHUBPORTFOLIO_MANUAL_ORDER'      => $config['mundophpbb_githubportfolio_manual_order'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_SELECTED_REPOS'    => $config['mundophpbb_githubportfolio_selected_repos'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_HIDDEN_REPOS'      => $config['mundophpbb_githubportfolio_hidden_repos'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_SHOW_PORTAL_BLOCK' => $show_portal_block,
            'MUNDOPHPBB_GITHUBPORTFOLIO_PORTAL_TITLE'      => $config['mundophpbb_githubportfolio_portal_title'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_PORTAL_LIMIT'      => isset($config['mundophpbb_githubportfolio_portal_limit']) ? (int) $config['mundophpbb_githubportfolio_portal_limit'] : 4,
            'MUNDOPHPBB_GITHUBPORTFOLIO_FEATURED_REPOS'    => $config['mundophpbb_githubportfolio_featured_repos'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_FEATURED_TITLE'    => $config['mundophpbb_githubportfolio_featured_title'] ?? '',
            'MUNDOPHPBB_GITHUBPORTFOLIO_REPO_DISCUSSIONS'  => $config['mundophpbb_githubportfolio_repo_discussions'] ?? '',
            'ACP_GITHUBPORTFOLIO_VISIBLE_TOTAL'            => count($all_repos),
            'ACP_GITHUBPORTFOLIO_FEATURED_TOTAL'           => count($featured_repos),
            'ACP_GITHUBPORTFOLIO_REGULAR_TOTAL'            => count($regular_repos),
            'ACP_GITHUBPORTFOLIO_DISCUSSION_TOTAL'         => $discussion_total,
            'ACP_GITHUBPORTFOLIO_LANGUAGE_TOTAL'           => count($languages),
            'ACP_GITHUBPORTFOLIO_LAST_SYNC_COUNT'          => $user->lang('ACP_GITHUBPORTFOLIO_SYNC_REPO_COUNT', $last_sync_count),
            'ACP_GITHUBPORTFOLIO_LAST_SYNC'                => $last_sync_timestamp ? $user->format_date($last_sync_timestamp) : '',
            'S_ACP_GITHUBPORTFOLIO_HAS_LAST_SYNC'          => $last_sync_timestamp > 0,
            'S_ACP_GITHUBPORTFOLIO_ENABLED'                => !empty($config['mundophpbb_githubportfolio_enabled']),
            'S_ACP_GITHUBPORTFOLIO_HAS_USERNAME'           => $username !== '',
            'S_ACP_GITHUBPORTFOLIO_PORTAL_ENABLED'         => !empty($show_portal_block),
        ]);
    }
}
