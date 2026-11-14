<?php
/**
 *
 * GitHub Portfolio. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 MundoPHPBB
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mundophpbb\githubportfolio\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\template\template;
use phpbb\controller\helper;
use phpbb\config\config;
use phpbb\user;
use mundophpbb\githubportfolio\service\github_api;

class main_listener implements EventSubscriberInterface
{
    protected template $template;
    protected helper $helper;
    protected config $config;
    protected user $user;
    protected github_api $github_api;

    public function __construct(template $template, helper $helper, config $config, user $user, github_api $github_api)
    {
        $this->template = $template;
        $this->helper = $helper;
        $this->config = $config;
        $this->user = $user;
        $this->github_api = $github_api;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'core.user_setup' => 'load_language',
            'core.page_header' => 'add_template_data',
        ];
    }

    public function load_language($event): void
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'mundophpbb/githubportfolio',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function add_template_data($event = null): void
    {
        if (empty($this->config['mundophpbb_githubportfolio_enabled']))
        {
            return;
        }

        $this->template->assign_vars([
            'U_GITHUBPORTFOLIO_PAGE'    => $this->helper->route('mundophpbb_githubportfolio_page'),
            'S_GITHUBPORTFOLIO_ENABLED' => true,
            'GITHUBPORTFOLIO_USERNAME'  => $this->config['mundophpbb_githubportfolio_username'],
        ]);

        $show_portal_block = isset($this->config['mundophpbb_githubportfolio_show_portal_block'])
            ? !empty($this->config['mundophpbb_githubportfolio_show_portal_block'])
            : true;

        if (!$show_portal_block)
        {
            return;
        }

        $portal_limit = isset($this->config['mundophpbb_githubportfolio_portal_limit'])
            ? max(1, min(12, (int) $this->config['mundophpbb_githubportfolio_portal_limit']))
            : 4;

        $all_repos = $this->github_api->get_all_repositories();
        $featured_repos = $this->github_api->get_featured_repositories($all_repos);
        $regular_repos = $this->github_api->get_regular_repositories($all_repos);
        $discussion_total = 0;

        foreach ($all_repos as $repo)
        {
            if (!empty($repo['discussion_url']))
            {
                $discussion_total++;
            }
        }

        if (!empty($featured_repos))
        {
            $featured_repos = array_slice($featured_repos, 0, min(2, $portal_limit));
        }

        $remaining_slots = max(0, $portal_limit - count($featured_repos));
        if ($remaining_slots > 0)
        {
            $regular_repos = array_slice($regular_repos, 0, $remaining_slots);
        }
        else
        {
            $regular_repos = [];
        }

        foreach ($featured_repos as $repo)
        {
            $this->template->assign_block_vars('githubportfolio_portal_featured_repos', $this->build_template_row($repo));
        }

        foreach ($regular_repos as $repo)
        {
            $this->template->assign_block_vars('githubportfolio_portal_repos', $this->build_template_row($repo));
        }

        $portal_title = isset($this->config['mundophpbb_githubportfolio_portal_title'])
            ? trim((string) $this->config['mundophpbb_githubportfolio_portal_title'])
            : '';
        if ($portal_title === '')
        {
            $portal_title = $this->user->lang('GITHUBPORTFOLIO_PORTAL_TITLE_DEFAULT');
        }

        $featured_title = isset($this->config['mundophpbb_githubportfolio_featured_title'])
            ? trim((string) $this->config['mundophpbb_githubportfolio_featured_title'])
            : '';
        if ($featured_title === '')
        {
            $featured_title = $this->user->lang('GITHUBPORTFOLIO_FEATURED_TITLE_DEFAULT');
        }

        $has_portal_repos = !empty($featured_repos) || !empty($regular_repos);

        $last_sync_timestamp = !empty($this->config['mundophpbb_githubportfolio_last_sync_time'])
            ? (int) $this->config['mundophpbb_githubportfolio_last_sync_time']
            : 0;

        $this->template->assign_vars([
            'S_GITHUBPORTFOLIO_PORTAL_BLOCK'        => true,
            'S_GITHUBPORTFOLIO_HAS_PORTAL_REPOS'    => $has_portal_repos,
            'S_GITHUBPORTFOLIO_HAS_PORTAL_FEATURED' => !empty($featured_repos),
            'S_GITHUBPORTFOLIO_HAS_PORTAL_REGULAR'  => !empty($regular_repos),
            'S_GITHUBPORTFOLIO_HAS_LAST_SYNC'       => $last_sync_timestamp > 0,
            'GITHUBPORTFOLIO_LAST_SYNC'             => $last_sync_timestamp > 0 ? $this->user->format_date($last_sync_timestamp) : '',
            'GITHUBPORTFOLIO_PORTAL_TITLE'          => $portal_title,
            'GITHUBPORTFOLIO_FEATURED_TITLE'        => $featured_title,
            'GITHUBPORTFOLIO_PORTAL_REPO_TOTAL'     => count($all_repos),
            'GITHUBPORTFOLIO_PORTAL_DISCUSSION_TOTAL' => $discussion_total,
        ]);
    }

    protected function build_template_row(array $repo): array
    {
        $updated_at = !empty($repo['updated_at']) ? strtotime($repo['updated_at']) : false;

        return [
            'NAME'            => htmlspecialchars((string) ($repo['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'URL'             => htmlspecialchars((string) ($repo['html_url'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'DESCRIPTION'     => htmlspecialchars((string) ($repo['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'LANGUAGE'        => htmlspecialchars((string) ($repo['language'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'LANGUAGE_CLASS'  => $this->build_language_class((string) ($repo['language'] ?? '')),
            'STARS'           => (int) ($repo['stargazers_count'] ?? 0),
            'UPDATED_AT'      => $updated_at ? $this->user->format_date($updated_at) : '',
            'IS_FORK'         => !empty($repo['fork']),
            'IS_ARCHIVED'     => !empty($repo['archived']),
            'DISCUSSION_URL'  => htmlspecialchars((string) ($repo['discussion_url'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'HAS_DISCUSSION'  => !empty($repo['discussion_url']),
            'IS_FEATURED'     => !empty($repo['featured']),
        ];
    }

    protected function build_language_class(string $language): string
    {
        $language = strtolower(trim($language));
        if ($language === '')
        {
            return 'generic';
        }

        $language = preg_replace('/[^a-z0-9]+/', '-', $language);
        $language = trim((string) $language, '-');

        return $language !== '' ? $language : 'generic';
    }

}
